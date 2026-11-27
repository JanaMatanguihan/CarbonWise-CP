
import pandas as pd

# ============================================================
# SETTINGS
# ============================================================

INPUT_PATH = "data/tft_dataset.csv"
OUTPUT_PATH = "data/tft_dataset_electricity_v2.csv"

# ============================================================
# LOAD DATA
# ============================================================

print("Loading TFT dataset...")

df = pd.read_csv(INPUT_PATH)

df["record_date"] = pd.to_datetime(df["record_date"])

df = df.sort_values("time_idx").reset_index(drop=True)

# ============================================================
# CREATE ELECTRICITY-SPECIFIC HISTORICAL FEATURES
# ============================================================

print("Creating electricity historical features...")

df["electricity_lag_1"] = (
    df["electricity"].shift(1)
)

df["electricity_lag_7"] = (
    df["electricity"].shift(7)
)

df["electricity_lag_14"] = (
    df["electricity"].shift(14)
)

df["electricity_rolling_7"] = (
    df["electricity"]
    .shift(1)
    .rolling(window=7)
    .mean()
)

df["electricity_rolling_30"] = (
    df["electricity"]
    .shift(1)
    .rolling(window=30)
    .mean()
)

# ============================================================
# REMOVE ROWS WITH MISSING NEW FEATURES
# ============================================================

feature_columns = [
    "electricity_lag_1",
    "electricity_lag_7",
    "electricity_lag_14",
    "electricity_rolling_7",
    "electricity_rolling_30",
]

# Preserve all existing rows with valid features.
df = df.dropna(
    subset=feature_columns
).reset_index(drop=True)

# ============================================================
# VERIFY DATA
# ============================================================

print("\nNew electricity features:")

print(df[[
    "record_date",
    "electricity",
    "electricity_lag_1",
    "electricity_lag_7",
    "electricity_lag_14",
    "electricity_rolling_7",
    "electricity_rolling_30",
]].tail(10).to_string(index=False))

print("\nTotal rows:", len(df))

print("First date:", df["record_date"].min())

print("Last date:", df["record_date"].max())

print("\nDataset distribution:")

print(df["dataset"].value_counts())

# ============================================================
# SAVE
# ============================================================

df.to_csv(
    OUTPUT_PATH,
    index=False
)

print("\nElectricity dataset saved:")
print(OUTPUT_PATH)

print("\nFEATURE PREPARATION COMPLETED")
