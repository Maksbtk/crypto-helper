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

# Пути
BASE_DIR           = os.path.dirname(os.path.abspath(__file__))
MODELS_DIR         = os.path.join(BASE_DIR, 'models')
SCALER_PATH        = os.path.join(MODELS_DIR, 'scaler.joblib')
CLF_PATH           = os.path.join(MODELS_DIR, 'clf.joblib')
DATA_DIR           = os.path.join(BASE_DIR, 'data')
DEFAULT_TRAIN_FILE = os.path.join(DATA_DIR, 'train_data.json')

N_BARS = 30
M_BARS = 100

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
    """Тренируем модель и сохраняем её на диск."""
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
    dump(scaler_local, SCALER_PATH)
    dump(clf_local,    CLF_PATH)

    return {"status": "trained", "n_samples": int(len(y))}

@app.route("/mlDev/train", methods=["POST"])
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

@app.route("/mlDev/train-file", methods=["POST"])
def train_file():
    """
    Тренируем модель данными из файла JSON:
    { "trainFilePath": "/путь/к/файлу" }
    """
    try:
        body = request.get_json(silent=True) or {}
        p    = body.get('trainFilePath', DEFAULT_TRAIN_FILE)
        path = p if os.path.isabs(p) else os.path.join(BASE_DIR, p)

        if not os.path.exists(path):
            return jsonify(error=f"File not found: {path}"), 404

        with open(path, 'r') as f:
            raw = json.load(f)

        # Приводим dict.values() к списку, если нужно
        if isinstance(raw, dict):
            data = list(raw.values())
        else:
            data = raw

        if not isinstance(data, list) or not data:
            return jsonify(error="Unsupported or empty JSON format"), 400

        result = do_train(data)
        return jsonify(result)
    except ValueError as ve:
        return jsonify(error=str(ve)), 400
    except Exception as e:
        app.logger.error(f"Train-file error: {e}\n{traceback.format_exc()}")
        return jsonify(error="Train-file failed"), 500

@app.route("/mlDev/predict", methods=["POST"])
def predict():
    """Всегда загружаем модели с диска и делаем предсказание."""
    try:
        if not os.path.exists(SCALER_PATH) or not os.path.exists(CLF_PATH):
            return jsonify(error="Model not trained"), 400

        scaler = load(SCALER_PATH)
        clf    = load(CLF_PATH)

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

@app.route("/ml/predict-batch", methods=["POST"])
def predict_batch():
    import os, traceback
    from flask import request, jsonify
    from joblib import load
    import numpy as np

    SCALER_PATH = os.path.join(MODELS_DIR, 'scaler.joblib')
    CLF_PATH    = os.path.join(MODELS_DIR, 'clf.joblib')

    # 1) Подгрузка модели
    if not os.path.exists(SCALER_PATH) or not os.path.exists(CLF_PATH):
        # возвращаем единичный элемент со статусом error,
        # чтобы PHP увидел r['status'] === 'error'
        return jsonify([{
            'key': None,
            'status': 'error',
            'error': 'Model not trained'
        }])

    try:
        scaler = load(SCALER_PATH)
        clf    = load(CLF_PATH)
    except Exception as e:
        return jsonify([{
            'key': None,
            'status': 'error',
            'error': f'Failed to load model: {e}'
        }])

    # 2) Чтение и базовая валидация входа
    try:
        payloads = request.get_json(force=True)
        if not isinstance(payloads, list) or len(payloads) == 0:
            raise ValueError("Expected non-empty JSON array of payloads")
    except Exception as e:
        return jsonify([{
            'key': None,
            'status': 'error',
            'error': f'Invalid input: {e}'
        }])

    resp = []
    # 3) Для каждого payload
    for idx, p in enumerate(payloads):
        entry = {
            'key':           p.get('key'),
            'status':        None,
            'probabilities': None,
            'classes':       None,
            'error':         None
        }

        # 3.1) Проверяем поля
        missing = []
        for fld in ('key','candles','entry','tps','sl','direction'):
            if fld not in p:
                missing.append(fld)
        if missing:
            entry['status'] = 'error'
            entry['error']  = "Missing fields: " . implode(',', missing)
            resp.append(entry)
            continue

        # 3.2) featurize
        try:
            x = featurize(p, include_future=False).reshape(1, -1)
        except Exception as fe:
            entry['status'] = 'error'
            entry['error']  = f'Featurize failed: {fe}'
            resp.append(entry)
            continue

        # 3.3) predict
        try:
            Xs = scaler.transform(x)
            probs = clf.predict_proba(Xs)[0].tolist()
            classes = clf.classes_.tolist()
            entry['status']        = 'ok'
            entry['probabilities'] = probs
            entry['classes']       = classes
        except Exception as ex:
            entry['status'] = 'error'
            entry['error']  = f'Predict failed: {ex}'

        resp.append(entry)

    # 4) Возвращаем ровно массив ответов
    return jsonify(resp)
        
if __name__ == "__main__":
    app.run(host="0.0.0.0", port=8001, threaded=False)
