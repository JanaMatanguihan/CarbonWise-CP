
import pandas as pd
import numpy as np

v2 = pd.read_csv("data/tft_v2_validation_predictions.csv")
v3 = pd.read_csv("data/tft_v3_validation_predictions.csv")

v2["record_date"] = pd.to_datetime(v2["record_date"])
v3["record_date"] = pd.to_datetime(v3["record_date"])

# Match the predictions by date.
merged = v2.merge(
    v3,
    on="record_date",
    suffixes=("_v2", "_v3")
)

merged["month"] = merged["record_date"].dt.strftime("%B")

merged["error_v2"] = (
    merged["predicted_emission_v2"]
    - merged["actual_emission_v2"]
)

merged["error_v3"] = (
    merged["predicted_emission_v3"]
    - merged["actual_emission_v3"]
)

merged["abs_error_v2"] = merged["error_v2"].abs()
merged["abs_error_v3"] = merged["error_v3"].abs()

print("\n========================================")
print("CARBONWISE TFT V2 VS V3 DIAGNOSTICS")
print("========================================")

for start, end, label in [
    ("2026-01-01", "2026-01-30", "January"),
    ("2026-01-31", "2026-03-01", "Window 2"),
    ("2026-03-02", "2026-03-31", "March"),
    ("2026-04-01", "2026-04-30", "April"),
    ("2026-05-01", "2026-05-30", "May"),
    ("2026-05-31", "2026-06-29", "Late May-June"),
]:

    period = merged[
        (merged["record_date"] >= start)
        & (merged["record_date"] <= end)
    ]

    print(f"\n{label}: {start} to {end}")

    print("Actual average:",
          round(period["actual_emission_v2"].mean(), 2))

    print("V2 predicted average:",
          round(period["predicted_emission_v2"].mean(), 2))

    print("V3 predicted average:",
          round(period["predicted_emission_v3"].mean(), 2))

    print("V2 average signed error:",
          round(period["error_v2"].mean(), 2))

    print("V3 average signed error:",
          round(period["error_v3"].mean(), 2))

    print("V2 MAE:",
          round(period["abs_error_v2"].mean(), 2))

    print("V3 MAE:",
          round(period["abs_error_v3"].mean(), 2))

print("\n========================================")
print("DIAGNOSTICS COMPLETED")
print("========================================")
