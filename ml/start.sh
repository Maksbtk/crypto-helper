#!/usr/bin/env bash
# Переходим в директорию скрипта
#  / tmux new -s mlservice / tmux attach -t mlservice / tmux ls / source venv/bin/activate / lsof -iTCP:8000 -sTCP:LISTEN
# pkill -f serve_model_flask.py / chmod +x start.sh / ./start.sh
cd "$(dirname "$0")"

# Ограничиваем потоки (BLAS/OMP)
export OMP_NUM_THREADS=1
export OPENBLAS_NUM_THREADS=1
export MKL_NUM_THREADS=1
export VECLIB_MAXIMUM_THREADS=1

# Активируем venv, если есть
if [ -f "venv/bin/activate" ]; then
  source venv/bin/activate
fi

# Запускаем
exec python serve_model_flask.py