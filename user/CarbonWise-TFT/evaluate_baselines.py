
import pandas as pd
import numpy as np

from sklearn.metrics import (
    mean_absolute_error,
    mean_squared_error,
    r2_score
)

# SETTINGS
DATA_PATH = "data/tft_dataset.csv"

MAX_ENCODER_LENGTH = 90
MAX_PREDICTION_LENGTH = 30

# LOAD DATA
df = pd.read_csv(DATA_PATH)
df["record_date"] = pd.to_datetime(df["record_date"])
df = df.sort_values("time_idx").reset_index(drop=True)

validation_df = (
    df[df["dataset"] == "VALIDATION"]
    .sort_values("time_idx")
    .reset_index(drop=True)
)

# BASELINE EVALUATION
all_results = []

print("\n========================================")
print("BASELINE FORECAST EVALUATION")
print("========================================")

for start_idx in range(
    0,
    len(validation_df) - MAX_PREDICTION_LENGTH + 1,
    MAX_PREDICTION_LENGTH
):
    window = validation_df.iloc[
        start_idx:start_idx + MAX_PREDICTION_LENGTH
    ].copy()

    first_time_idx = int(window["time_idx"].min())

    # Get the 90-day encoder history plus prediction window
    history = df[
        (df["time_idx"] >= first_time_idx - MAX_ENCODER_LENGTH)
        & (df["time_idx"] < first_time_idx)
    ].copy()

    if len(history) != MAX_ENCODER_LENGTH:
        raise ValueError(
            f"Expected 90 history days, got {len(history)}"
        )

    actual = window["total_emission"].to_numpy(dtype=float)

    # BASELINE 1: PERSISTENCE
    last_observed = float(history["total_emission"].iloc[-1])

    persistence_predictions = np.full(
        MAX_PREDICTION_LENGTH,
        last_observed
    )

    # BASELINE 2: 7-DAY SEASONAL NAIVE
    # Recursive forecasting: each new prediction becomes
    # the value used seven days later.
    seasonal_history = history["total_emission"].tolist()
    seasonal_predictions = []

    for _ in range(MAX_PREDICTION_LENGTH):
        prediction = float(seasonal_history[-7])
        seasonal_predictions.append(prediction)
        seasonal_history.append(prediction)

    seasonal_predictions = np.array(seasonal_predictions)

    methods = {
        "Persistence": persistence_predictions,
        "Seasonal Naive (7-day)": seasonal_predictions
    }

    print(
        f"\nWindow: {window['record_date'].min().date()} "
        f"to {window['record_date'].max().date()}"
    )

    for method_name, predictions in methods.items():

        mae = mean_absolute_error(actual, predictions)

        rmse = np.sqrt(
            mean_squared_error(actual, predictions)
        )

        r2 = r2_score(actual, predictions)

        print(
            f"{method_name}: "
            f"MAE={mae:.2f} | "
            f"RMSE={rmse:.2f} | "
            f"R2={r2:.4f}"
        )

        for i in range(MAX_PREDICTION_LENGTH):
            all_results.append({
                "record_date": window["record_date"].iloc[i],
                "window": start_idx // MAX_PREDICTION_LENGTH + 1,
                "actual_emission": actual[i],
                "method": method_name,
                "predicted_emission": predictions[i]
            })


# SAVE RESULTS
results = pd.DataFrame(all_results)

output_path = "data/tft_v2_baseline_predictions.csv"

results.to_csv(output_path, index=False)

# OVERALL METRICS
print("\n========================================")
print("OVERALL BASELINE RESULTS")
print("========================================")

for method_name, group in results.groupby("method"):

    actual = group["actual_emission"].to_numpy()
    predicted = group["predicted_emission"].to_numpy()

    mae = mean_absolute_error(actual, predicted)

    rmse = np.sqrt(
        mean_squared_error(actual, predicted)
    )

    r2 = r2_score(actual, predicted)

    print(f"\n{method_name}")
    print(f"MAE:  {mae:.2f}")
    print(f"RMSE: {rmse:.2f}")
    print(f"R2:   {r2:.4f}")

print("\nBaseline predictions saved to:")
print(output_path)
