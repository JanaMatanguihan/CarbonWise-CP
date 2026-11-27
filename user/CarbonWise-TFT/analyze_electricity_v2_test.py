
import pandas as pd
import numpy as np
import matplotlib.pyplot as plt
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score

# ============================================================
# FILES
# ============================================================

INPUT_FILE = "data/electricity_v2_test_predictions.csv"

OUTPUT_DAILY = "data/electricity_v2_test_error_analysis.csv"
OUTPUT_SUMMARY = "data/electricity_v2_test_error_summary.csv"

# ============================================================
# LOAD DATA
# ============================================================

df = pd.read_csv(INPUT_FILE)
df["record_date"] = pd.to_datetime(df["record_date"])

required_columns = [
    "record_date",
    "actual_electricity",
    "original_prediction",
    "v2_prediction",
]

missing = [col for col in required_columns if col not in df.columns]

if missing:
    raise ValueError(f"Missing required columns: {missing}")

# ============================================================
# CALCULATE ERRORS
# ============================================================

df["original_error"] = (
    df["actual_electricity"] - df["original_prediction"]
)

df["v2_error"] = (
    df["actual_electricity"] - df["v2_prediction"]
)

df["original_absolute_error"] = df["original_error"].abs()
df["v2_absolute_error"] = df["v2_error"].abs()

df["original_squared_error"] = df["original_error"] ** 2
df["v2_squared_error"] = df["v2_error"] ** 2

# Positive bias means underprediction.
# Negative bias means overprediction.

# ============================================================
# SUMMARY METRICS
# ============================================================

actual = df["actual_electricity"]

summary = []

for model_name, prediction_column, error_column in [
    ("Original Electricity TFT", "original_prediction", "original_error"),
    ("Electricity V2 TFT", "v2_prediction", "v2_error"),
]:

    predicted = df[prediction_column]
    errors = df[error_column]

    summary.append({
        "model": model_name,
        "MAE": mean_absolute_error(actual, predicted),
        "RMSE": np.sqrt(mean_squared_error(actual, predicted)),
        "R2": r2_score(actual, predicted),
        "Mean_Error_Bias": errors.mean(),
        "Mean_Actual": actual.mean(),
        "Mean_Predicted": predicted.mean(),
        "Max_Absolute_Error": errors.abs().max(),
        "Min_Prediction": predicted.min(),
        "Max_Prediction": predicted.max(),
    })

summary_df = pd.DataFrame(summary)

# ============================================================
# WORST PREDICTION DAYS
# ============================================================

print("\n" + "=" * 70)
print("FIVE WORST DAYS - ORIGINAL MODEL")
print("=" * 70)

print(
    df.nlargest(5, "original_absolute_error")[
        [
            "record_date",
            "actual_electricity",
            "original_prediction",
            "original_error",
            "original_absolute_error",
        ]
    ].to_string(index=False)
)

print("\n" + "=" * 70)
print("FIVE WORST DAYS - ELECTRICITY V2")
print("=" * 70)

print(
    df.nlargest(5, "v2_absolute_error")[
        [
            "record_date",
            "actual_electricity",
            "v2_prediction",
            "v2_error",
            "v2_absolute_error",
        ]
    ].to_string(index=False)
)

# ============================================================
# SUMMARY
# ============================================================

print("\n" + "=" * 70)
print("ERROR ANALYSIS SUMMARY")
print("=" * 70)

print(summary_df.to_string(index=False))

# ============================================================
# SAVE ANALYSIS
# ============================================================

df.to_csv(OUTPUT_DAILY, index=False)
summary_df.to_csv(OUTPUT_SUMMARY, index=False)

print("\nSaved:")
print(OUTPUT_DAILY)
print(OUTPUT_SUMMARY)

# ============================================================
# VISUALIZATION 1: ACTUAL VS PREDICTED
# ============================================================

plt.figure(figsize=(14, 6))

plt.plot(
    df["record_date"],
    df["actual_electricity"],
    marker="o",
    label="Actual Electricity",
)

plt.plot(
    df["record_date"],
    df["original_prediction"],
    marker="o",
    label="Original TFT",
)

plt.plot(
    df["record_date"],
    df["v2_prediction"],
    marker="o",
    label="Electricity V2",
)

plt.title("Actual vs Predicted Electricity Emissions")
plt.xlabel("Date")
plt.ylabel("Electricity Emissions")
plt.xticks(rotation=45)
plt.legend()
plt.grid(True)
plt.tight_layout()

plt.savefig(
    "data/electricity_v2_actual_vs_predicted.png",
    dpi=300,
)

plt.show()

# ============================================================
# VISUALIZATION 2: DAILY ABSOLUTE ERRORS
# ============================================================

plt.figure(figsize=(14, 6))

plt.plot(
    df["record_date"],
    df["original_absolute_error"],
    marker="o",
    label="Original TFT Error",
)

plt.plot(
    df["record_date"],
    df["v2_absolute_error"],
    marker="o",
    label="Electricity V2 Error",
)

plt.title("Daily Absolute Prediction Errors")
plt.xlabel("Date")
plt.ylabel("Absolute Error")
plt.xticks(rotation=45)
plt.legend()
plt.grid(True)
plt.tight_layout()

plt.savefig(
    "data/electricity_v2_daily_errors.png",
    dpi=300,
)

plt.show()

# ============================================================
# VISUALIZATION 3: ERROR DISTRIBUTION
# ============================================================

plt.figure(figsize=(10, 6))

plt.boxplot(
    [
        df["original_error"],
        df["v2_error"],
    ],
    tick_labels=[
        "Original TFT",
        "Electricity V2",
    ],
)

plt.title("Prediction Error Distribution")
plt.ylabel("Prediction Error (Actual - Predicted)")
plt.grid(True)
plt.tight_layout()

plt.savefig(
    "data/electricity_v2_error_distribution.png",
    dpi=300,
)

plt.show()

print("\nError analysis completed successfully.")
