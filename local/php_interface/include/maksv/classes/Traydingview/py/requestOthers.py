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
SYMBOL      = 'OTHERS'  # Total Market Cap Excluding Top 10 на TradingView
EXCHANGE    = 'CRYPTOCAP'
NUM_BARS    =  402
INTERVALS   = {
    '5m':  Interval.in_5_minute,
    '15m': Interval.in_15_minute,
    '1h':  Interval.in_1_hour,
    '4h':  Interval.in_4_hour,
}

# Ретрай-конфиг
MAX_RETRIES   = 15       # макс. число попыток в fetch_data
BASE_DELAY    = 1.6     # базовая пауза (сек) в экспоненциальном бэкоффе
JITTER_FACTOR = 0.8     # флуктуация паузы ±30%

# Пути
OUTPUT_JSON = '/home/c/cz06737izol/crypto/public_html/upload/traydingviewExchange/total_ex_top10.json'
LOG_DIR     = '/home/c/cz06737izol/crypto/public_html/devlogs/traydingview/others'

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
    Применяем экспоненциальный бэкофф, джиттер и пересоздание сессии при SSL-ошибках.
    """
    log(f"  -> fetch_data start for {tf_name}")
    attempt = 0
    tv = TvDatafeed()  # каждый fetch_data начинает с новой сессии

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
                # Успешно получили данные
                df = df.reset_index()
                df['datetime'] = df['datetime'].dt.strftime('%Y-%m-%d %H:%M:%S')
                return df.to_dict(orient='records')

        except ssl.SSLError as e:
            log(f"     attempt {attempt}: SSL timeout для {tf_name}: {e}")
            tv = TvDatafeed()  # пересоздаём клиента

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
        log('Process started')
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
            time.sleep(0.5)  # пауза между разными таймфреймами

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
