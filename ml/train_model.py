import os
import json
import glob
import numpy as np
import pandas as pd
from sklearn.preprocessing import StandardScaler
from sklearn.ensemble import RandomForestClassifier
from joblib import dump

# 1) Загрузка и подготовка данных
data_dir = os.path.join(os.path.dirname(__file__), 'data')
files = glob.glob(os.path.join(data_dir, '*.json'))

rows = []
for path in files:
    with open(path, 'r') as f:
        js = json.load(f)
    ta = js['ta']
    market = js.get('marketInfo', {})
    outcome = js['outcome']

    # Собираем признаки
    row = {
        'macd_line':        ta['macd_line'],
        'macd_hist':        ta['histogram'],
        'impulse_macd':     ta.get('impulse_macd', 0),
        'stoch_k':          ta['stoch_k'],
        'stoch_d':          ta['stoch_d'],
        'adx_5m':           ta.get('adx_5m', 0),
        'adx_15m':          ta.get('adx_15m', 0),
        'totalCapExTop10':  market.get('totalCapExTop10', np.nan),
        'btcDominance':     market.get('btcDominance', np.nan),
        # метка: 1 = WIN, 0 = LOSS
        'label':            1 if outcome['label']=='WIN' else 0
    }
    rows.append(row)

df = pd.DataFrame(rows).dropna()

# 2) Делим на X и y
X = df.drop('label', axis=1)
y = df['label']

# 3) Масштабирование
scaler = StandardScaler().fit(X)
X_scaled = scaler.transform(X)

# 4) Обучение классификатора
clf = RandomForestClassifier(n_estimators=100, random_state=42)
clf.fit(X_scaled, y)

# 5) Сохранение артефактов
out_dir = os.path.join(os.path.dirname(__file__), 'models')
os.makedirs(out_dir, exist_ok=True)
dump(scaler, os.path.join(out_dir, 'scaler.joblib'))
dump(clf,    os.path.join(out_dir, 'clf.joblib'))

print("Training complete. Models saved to", out_dir)
