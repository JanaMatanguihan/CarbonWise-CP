import pandas as pd
import numpy as np

from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score


# ============================================================
# SETTINGS
# ============================================================

DATA_PATH = "tft/data/tft_dataset.csv"
TFT_PATH = "tft/data/tft_test_predictions.csv"

TEST_START = "2026-07-01"
TEST_END = "2026-07-20"


# ============================================================
# LOAD DATA
# ============================================================

print("Loading dataset...")

df = pd.read_csv(DATA_PATH)

df["record_date"] = pd.to_datetime(df["record_date"])

df = df.sort_values("record_date").reset_index(drop=True)


# ============================================================
# TEST DATA
# ============================================================

test = df[
    (df["record_date"] >= TEST_START) &
    (df["record_date"] <= TEST_END)
].copy()


# ============================================================
# PRE-TEST DATA
# ============================================================

history = df[
    df["record_date"] < TEST_START
].copy()


actual = test["total_emission"].values


# ============================================================
# BASELINE 1 — 30-DAY MEAN
# ============================================================

mean_30 = history.tail(30)["total_emission"].mean()

prediction_30 = np.full(
    len(test),
    mean_30
)


# ============================================================
# BASELINE 2 — 7-DAY MEAN
# ============================================================

mean_7 = history.tail(7)["total_emission"].mean()

prediction_7 = np.full(
    len(test),
    mean_7
)


# ============================================================
# BASELINE 3 — LAST OBSERVED VALUE
# ============================================================

last_value = history.iloc[-1]["total_emission"]

prediction_last = np.full(
    len(test),
    last_value
)


# ============================================================
# TFT
# ============================================================

tft = pd.read_csv(TFT_PATH)

tft_prediction = tft[
    "predicted_emission"
].values[:len(test)]


# ============================================================
# METRIC FUNCTION
# ============================================================

def calculate_metrics(actual, predicted):

    mae = mean_absolute_error(
        actual,
        predicted
    )

    rmse = np.sqrt(
        mean_squared_error(
            actual,
            predicted
        )
    )

    r2 = r2_score(
        actual,
        predicted
    )

    return mae, rmse, r2


# ============================================================
# CALCULATE RESULTS
# ============================================================

results = []


for name, prediction in [

    ("30-Day Mean", prediction_30),

    ("7-Day Mean", prediction_7),

    ("Last Value", prediction_last),

    ("TFT", tft_prediction)

]:

    mae, rmse, r2 = calculate_metrics(
        actual,
        prediction
    )

    results.append({

        "model": name,

        "MAE": mae,

        "RMSE": rmse,

        "R2": r2

    })


results_df = pd.DataFrame(results)


# ============================================================
# DISPLAY RESULTS
# ============================================================

print("\n========================================")
print("FORECASTING MODEL COMPARISON")
print("========================================")

print(
    results_df.to_string(
        index=False,
        formatters={
            "MAE": "{:.2f}".format,
            "RMSE": "{:.2f}".format,
            "R2": "{:.4f}".format
        }
    )
)


# ============================================================
# BEST MODEL
# ============================================================

best_model = results_df.loc[
    results_df["MAE"].idxmin()
]

print("\n========================================")
print("BEST MODEL BY MAE")
print("========================================")

print(
    "Model:",
    best_model["model"]
)

print(
    f"MAE: {best_model['MAE']:.2f}"
)

print(
    f"RMSE: {best_model['RMSE']:.2f}"
)

print(
    f"R²: {best_model['R2']:.4f}"
)


# ============================================================
# SAVE RESULTS
# ============================================================

output_path = (
    "tft/data/tft_model_comparison.csv"
)

results_df.to_csv(
    output_path,
    index=False
)

print("\nResults saved to:")
print(output_path)