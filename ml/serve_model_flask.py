#!/usr/bin/env python3
import os

# Жёстко ограничиваем потоки BLAS/OMP до импорта numpy/joblib
os.environ["OMP_NUM_THREADS"]         = "1"
os.environ["OPENBLAS_NUM_THREADS"]    = "1"
os.environ["MKL_NUM_THREADS"]         = "1"
os.environ["VECLIB_MAXIMUM_THREADS"]  = "1"

from flask import Flask, request, jsonify
from joblib import load, dump
from sklearn.preprocessing import StandardScaler
from sklearn.ensemble    import RandomForestClassifier
import numpy as np, traceback

app = Flask(__name__)
MODELS_DIR = 'models'

# Параметры: 30 баров до сигнала, 100 баров после (для метки)
N_BARS = 30
M_BARS = 100

# Попытка загрузить сохранённую модель (если есть)
scaler = clf = None
try:
    scaler = load(f"{MODELS_DIR}/scaler.joblib")
    clf    = load(f"{MODELS_DIR}/clf.joblib")
    app.logger.info("ML models loaded.")
except Exception:
    app.logger.warning("No valid model on startup, train first.")


def featurize(sig, include_future: bool):
    """
    Формируем вектор признаков и (если include_future=True) метку.
    Признаки:
      1) direction_flag = 1.0 для long, 0.0 для short
      2) N_BARS (30) × три базовых признака: (c/o - 1), ((h-l)/o), (v/o)
      3) Признаки TP/SL: (tp_i-entry)/entry, (sl-entry)/entry
    Если include_future=False, возвращаем только X (np.array).
    Если include_future=True, возвращаем (X, label), где label ∈ {0,1,…,len(tps)}.
    """

    feats = []

    # 1) direction_flag
    dir_str = sig.get('direction', 'long')
    feats.append(1.0 if dir_str == 'long' else 0.0)

    # 2) Берём последние N_BARS баров (каждый – {'o','h','l','c','v'})
    hist = sig['candles'][-N_BARS:]
    for b in hist:
        o = b['o']; h = b['h']; l = b['l']; c = b['c']; v = b['v']
        feats += [(c / o - 1), ((h - l) / o), (v / o)]

    # 3) Признаки TP/SL
    entry = sig['entry']
    tps   = sig['tps']  # список float
    sl    = sig['sl']
    for tp in tps:
        feats.append((tp - entry) / entry)
    feats.append((sl - entry) / entry)

    if not include_future:
        return np.array(feats)

    # 4) Вычисление метки (label = 0..len(tps)), с учётом long/short
    label = 0
    if dir_str == 'long':
        for b in sig.get('future', [])[:M_BARS]:
            low, high = b['l'], b['h']
            if low <= sl:
                label = 0
                break
            for idx, tp in enumerate(tps, start=1):
                if high >= tp:
                    label = idx
                    break
            if label != 0:
                break
    else:  # short
        for b in sig.get('future', [])[:M_BARS]:
            low, high = b['l'], b['h']
            if high >= sl:
                label = 0
                break
            for idx, tp in enumerate(tps, start=1):
                if low <= tp:
                    label = idx
                    break
            if label != 0:
                break

    return np.array(feats), label


@app.route("/ml/train", methods=["POST"])
def train():
    global scaler, clf
    data = request.get_json()
    if not isinstance(data, list) or not data:
        return jsonify(error="Expected non-empty list"), 400

    Xs, ys = [], []
    try:
        for sig in data:
            dir_str = sig.get('direction')
            if dir_str not in ('long', 'short'):
                return jsonify(error="Each signal must contain 'direction':'long' or 'direction':'short'"), 400

            x, y = featurize(sig, include_future=True)
            Xs.append(x)
            ys.append(y)

        X = np.vstack(Xs)
        y = np.array(ys)

        app.logger.info(f"Train labels distribution: {np.bincount(y)}")

        scaler = StandardScaler().fit(X)
        Xs_s   = scaler.transform(X)

        clf = RandomForestClassifier(
            n_estimators=200,
            class_weight='balanced',
            random_state=42
        ).fit(Xs_s, y)

        os.makedirs(MODELS_DIR, exist_ok=True)
        dump(scaler, f"{MODELS_DIR}/scaler.joblib")
        dump(clf,    f"{MODELS_DIR}/clf.joblib")

        return jsonify(status="trained", n_samples=int(len(y)))
    except Exception as e:
        app.logger.error(f"Train failed: {e}\n{traceback.format_exc()}")
        return jsonify(error="Train failed"), 500


@app.route("/ml/predict", methods=["POST"])
def predict():
    global scaler, clf
    if scaler is None or clf is None:
        return jsonify(error="Model not trained"), 400

    sig = request.get_json()
    dir_str = sig.get('direction')
    if dir_str not in ('long', 'short'):
        return jsonify(error="Predict requires 'direction':'long' or 'direction':'short'"), 400

    try:
        x   = featurize(sig, include_future=False).reshape(1, -1)
        x_s = scaler.transform(x)
        probs = clf.predict_proba(x_s)[0].tolist()
        return jsonify(status="ok", probabilities=probs)
    except Exception as e:
        app.logger.error(f"Predict failed: {e}\n{traceback.format_exc()}")
        return jsonify(error="Predict failed"), 500


if __name__ == "__main__":
    os.environ["OMP_NUM_THREADS"]         = "1"
    os.environ["OPENBLAS_NUM_THREADS"]    = "1"
    os.environ["MKL_NUM_THREADS"]         = "1"
    os.environ["VECLIB_MAXIMUM_THREADS"]  = "1"
    app.run(host="0.0.0.0", port=8000, threaded=False)
