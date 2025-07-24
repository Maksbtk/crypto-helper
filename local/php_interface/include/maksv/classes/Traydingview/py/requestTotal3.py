#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import time
import random
import ssl
import json
import tempfile
from datetime import datetime
from tvDatafeed import TvDatafeed, Interval
import pandas as pd

# Ограничиваем количество потоков для OpenBLAS
os.environ['OPENBLAS_NUM_THREADS'] = '1'
os.environ['OMP_NUM_THREADS']      = '1'

# === Параметры ===
SYMBOL      = 'TOTAL3'      # Total Market Cap Excluding BTC & ETH на TradingView
EXCHANGE    = 'CRYPTOCAP'
NUM_BARS    = 402
INTERVALS   = {
    '15m': Interval.in_15_minute,
    '5m':  Interval.in_5_minute,
    '1h':  Interval.in_1_hour,
    '4h':  Interval.in_4_hour,
}

# Ретрай-конфиг
MAX_RETRIES   = 5       # макс. число попыток
BASE_DELAY    = 1.0     # базовая пауза (сек) для эксп. бэкоффа
JITTER_FACTOR = 0.3     # флуктуация паузы ±30%

# Пути
OUTPUT_JSON = '/home/c/cz06737izol/crypto/public_html/upload/traydingviewExchange/total3.json'
LOG_DIR     = '/home/c/cz06737izol/crypto/public_html/devlogs/traydingview/total3'

# Создаём директории, если их нет
os.makedirs(os.path.dirname(OUTPUT_JSON), exist_ok=True)
os.makedirs(LOG_DIR, exist_ok=True)

# Файл лога вида YYYYMM.txt
log_file = os.path.join(LOG_DIR, datetime.now().strftime('%Y%m') + '.txt')

def log(message: str):
    ts = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    with open(log_file, 'a') as lf:
        lf.write(f"[{ts}] {message}\n")

def fetch_data(interval, tf_name):
    """
    Возвращаем список свечей или пустой список.
    Экспоненциальный бэкофф, джиттер, пересоздание сессии при SSL-таймаутах.
    """
    log(f"  -> fetch_data start for {tf_name}")
    attempt = 0
    tv = TvDatafeed()

    while attempt < MAX_RETRIES:
        attempt += 1
        try:
            df = tv.get_hist(
                symbol   = SYMBOL,
                exchange = EXCHANGE,
                interval = interval,
                n_bars   = NUM_BARS
            )
            if df is None or df.empty:
                log(f"     attempt {attempt}: пустой DataFrame для {tf_name}")
            else:
                df = df.reset_index()
                df['datetime'] = df['datetime'].dt.strftime('%Y-%m-%d %H:%M:%S')
                return df.to_dict(orient='records')

        except ssl.SSLError as e:
            log(f"     attempt {attempt}: SSL timeout для {tf_name}: {e}")
            tv = TvDatafeed()  # пересоздаём клиент

        except Exception as e:
            log(f"     attempt {attempt}: ошибка для {tf_name}: {e}")

        # экспоненциальный бэкофф + джиттер
        if attempt < MAX_RETRIES:
            backoff = BASE_DELAY * (2 ** (attempt - 1))
            jitter = backoff * JITTER_FACTOR
            sleep_time = backoff + random.uniform(-jitter, +jitter)
            log(f"     пауза {sleep_time:.2f}s перед попыткой {attempt+1} для {tf_name}")
            time.sleep(sleep_time)

    log(f"     все {MAX_RETRIES} попыток провалились для {tf_name}")
    return []

def main():
    try:
        log('Process started for TOTAL3')
        now = datetime.now()
        timestamp   = int(now.timestamp())
        server_time = now.strftime('%Y-%m-%d %H:%M:%S')
        result = {
            'timestamp': timestamp,
            'server_time': server_time,
            'data': {}
        }

        for tf_name, tf_interval in INTERVALS.items():
            log(f'Fetching {tf_name} data')
            candles = fetch_data(tf_interval, tf_name)
            result['data'][tf_name] = candles
            time.sleep(0.5)  # пауза между таймфреймами

        # Атомарная запись JSON
        dir_out = os.path.dirname(OUTPUT_JSON)
        with tempfile.NamedTemporaryFile('w', dir=dir_out, delete=False, encoding='utf-8') as tmp:
            json.dump(result, tmp, indent=2, ensure_ascii=False)
            tmp_path = tmp.name
        os.replace(tmp_path, OUTPUT_JSON)

        log('JSON written to ' + OUTPUT_JSON)
        log('Process completed successfully')
        log('______________________________')
        print('OK')
    except Exception as e:
        log('Error: ' + str(e))
        log('______________________________')
        print('ERROR: ' + str(e))
        exit(1)

if __name__ == '__main__':
    main()
