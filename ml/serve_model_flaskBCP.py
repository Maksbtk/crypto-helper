#!/usr/bin/env python3
import os
import json
import traceback
from flask import Flask, request, jsonify
from joblib import load, dump
from sklearn.preprocessing import StandardScaler
from sklearn.ensemble import RandomForestClassifier
import numpy as np

# Ограничиваем потоки
os.environ["OMP_NUM_THREADS"]         = "1"
os.environ["OPENBLAS_NUM_THREADS"]    = "1"
os.environ["MKL_NUM_THREADS"]         = "1"
os.environ["VECLIB_MAXIMUM_THREADS"]  = "1"

app = Flask(__name__)

# Корень проекта (там, где лежит этот файл)
BASE_DIR           = os.path.dirname(os.path.abspath(__file__))
MODELS_DIR         = os.path.join(BASE_DIR, 'models')
DATA_DIR           = os.path.join(BASE_DIR, 'data')
DEFAULT_TRAIN_FILE = os.path.join(DATA_DIR, 'train_data.json')

N_BARS = 30
M_BARS = 100

# Попытка загрузить уже обученные модели
scaler = clf = None
try:
    scaler = load(os.path.join(MODELS_DIR, 'scaler.joblib'))
    clf    = load(os.path.join(MODELS_DIR, 'clf.joblib'))
    app.logger.info("ML models loaded.")
except Exception:
    app.logger.warning("No model on startup; train first.")


def featurize(sig, include_future: bool):
    feats = []
    dir_str = sig.get('direction', 'long')
    feats.append(1.0 if dir_str == 'long' else 0.0)

    hist = sig['candles'][-N_BARS:]
    for b in hist:
        o, h, l, c, v = b['o'], b['h'], b['l'], b['c'], b['v']
        feats += [(c / o - 1), ((h - l) / o), (v / o)]

    entry = sig['entry']
    tps   = sig['tps']
    sl    = sig['sl']
    for tp in tps:
        feats.append((tp - entry) / entry)
    feats.append((sl - entry) / entry)

    if not include_future:
        return np.array(feats)

    label = 0
    if dir_str == 'long':
        for b in sig.get('future', [])[:M_BARS]:
            low, high = b['l'], b['h']
            if low <= sl:
                break
            for idx, tp in enumerate(tps, start=1):
                if high >= tp:
                    label = idx
                    break
            if label != 0:
                break
    else:
        for b in sig.get('future', [])[:M_BARS]:
            low, high = b['l'], b['h']
            if high >= sl:
                break
            for idx, tp in enumerate(tps, start=1):
                if low <= tp:
                    label = idx
                    break
            if label != 0:
                break

    return np.array(feats), label


def do_train(data):
    """Тренируем модель на списке сигналов data."""
    Xs, ys = [], []
    for sig in data:
        dir_str = sig.get('direction')
        if dir_str not in ('long', 'short'):
            raise ValueError("Each signal must contain 'direction':'long' or 'direction':'short'")
        x, y = featurize(sig, include_future=True)
        Xs.append(x)
        ys.append(y)

    X = np.vstack(Xs)
    y = np.array(ys)
    app.logger.info(f"Train labels distribution: {np.bincount(y)}")

    scaler_local = StandardScaler().fit(X)
    Xs_s         = scaler_local.transform(X)
    clf_local    = RandomForestClassifier(
        n_estimators=200,
        class_weight='balanced',
        random_state=42
    ).fit(Xs_s, y)

    os.makedirs(MODELS_DIR, exist_ok=True)
    dump(scaler_local, os.path.join(MODELS_DIR, 'scaler.joblib'))
    dump(clf_local,    os.path.join(MODELS_DIR, 'clf.joblib'))

    # чтобы в этом же процессе predict увидел новые модели
    global scaler, clf
    scaler = scaler_local
    clf    = clf_local

    return {"status": "trained", "n_samples": int(len(y))}


@app.route("/ml/train", methods=["POST"])
def train():
    try:
        data = request.get_json()
        if not isinstance(data, list) or not data:
            return jsonify(error="Expected non-empty list"), 400
        result = do_train(data)
        return jsonify(result)
    except ValueError as ve:
        return jsonify(error=str(ve)), 400
    except Exception as e:
        app.logger.error(f"Train error: {e}\n{traceback.format_exc()}")
        return jsonify(error="Train failed"), 500


@app.route("/ml/train-file", methods=["POST"])
def train_file():
    """
    Ожидает JSON:
      { "trainFilePath": "/полный/или/относительный/путь" }
    Если отсутствует — берёт DEFAULT_TRAIN_FILE.
    Поддерживает JSON формата list или dict{key:signal}.
    """
    try:
        body = request.get_json(silent=True) or {}
        p    = body.get('trainFilePath', DEFAULT_TRAIN_FILE)
        path = p if os.path.isabs(p) else os.path.join(BASE_DIR, p)

        if not os.path.exists(path):
            return jsonify(error=f"File not found: {path}"), 404

        raw = json.load(open(path, 'r'))
        if isinstance(raw, dict):
            data = list(raw.values())
        elif isinstance(raw, list):
            data = raw
        else:
            return jsonify(error="Unsupported JSON format in train file"), 400

        if not data:
            return jsonify(error="No data in train file"), 400

        result = do_train(data)
        return jsonify(result)

    except ValueError as ve:
        return jsonify(error=str(ve)), 400
    except Exception as e:
        app.logger.error(f"Train-file error: {e}\n{traceback.format_exc()}")
        return jsonify(error="Train-file failed"), 500


@app.route("/ml/predict", methods=["POST"])
def predict():
    global scaler, clf
    # если в памяти нет моделей — подгружаем их с диска
    if scaler is None or clf is None:
        try:
            scaler = load(os.path.join(MODELS_DIR, 'scaler.joblib'))
            clf    = load(os.path.join(MODELS_DIR, 'clf.joblib'))
            app.logger.info("ML models reloaded for predict.")
        except Exception:
            return jsonify(error="Model not trained"), 400

    try:
        sig = request.get_json()
        dir_str = sig.get('direction')
        if dir_str not in ('long', 'short'):
            return jsonify(error="Predict requires 'direction':'long' or 'direction':'short'"), 400

        x     = featurize(sig, include_future=False).reshape(1, -1)
        probs = clf.predict_proba(scaler.transform(x))[0].tolist()
        return jsonify(status="ok", probabilities=probs)
    except Exception as e:
        app.logger.error(f"Predict error: {e}\n{traceback.format_exc()}")
        return jsonify(error="Predict failed"), 500


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=8000, threaded=False)
