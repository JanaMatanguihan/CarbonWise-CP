import pandas as pd

# Load the exported daily carbon dataset
file_path = "tft/data/daily_carbon_emissions_rows.csv"

df = pd.read_csv(file_path)

# Convert record_date to datetime
df["record_date"] = pd.to_datetime(df["record_date"])

print("Original rows:", len(df))
print("First date:", df["record_date"].min())
print("Last date:", df["record_date"].max())

# Keep only the clean historical period
df = df[
    (df["record_date"] >= "2021-01-01") &
    (df["record_date"] <= "2026-07-20")
].copy()

# Sort chronologically
df = df.sort_values("record_date").reset_index(drop=True)

# Time-based features
df["day_of_week"] = df["record_date"].dt.dayofweek
df["month"] = df["record_date"].dt.month
df["day_of_month"] = df["record_date"].dt.day
df["is_weekend"] = (
    df["day_of_week"] >= 5
).astype(int)

# Historical lag features
df["emission_lag_1"] = df["total_emission"].shift(1)
df["emission_lag_7"] = df["total_emission"].shift(7)
df["emission_lag_14"] = df["total_emission"].shift(14)

# Historical rolling averages
df["emission_rolling_7"] = (
    df["total_emission"]
    .shift(1)
    .rolling(window=7)
    .mean()
)

df["emission_rolling_30"] = (
    df["total_emission"]
    .shift(1)
    .rolling(window=30)
    .mean()
)

# Remove rows without enough historical information
df = df.dropna(
    subset=[
        "emission_lag_1",
        "emission_lag_7",
        "emission_lag_14",
        "emission_rolling_7",
        "emission_rolling_30"
    ]
).copy()

print("\nAfter filtering:")
print("Rows:", len(df))
print("First date:", df["record_date"].min())
print("Last date:", df["record_date"].max())

# Check missing values
print("\nMissing values:")
print(df.isnull().sum())

# Check duplicate dates
print("\nDuplicate dates:", df["record_date"].duplicated().sum())

# Display the first 10 records
print("\nFirst 10 records:")
print(df.head(10))

# Display the last 10 records
print("\nLast 10 records:")
print(df.tail(10))

# Create TFT time index
df["time_idx"] = range(len(df))

# Create dataset split
df["dataset"] = "TRAIN"

df.loc[
    (df["record_date"] >= "2026-01-01") &
    (df["record_date"] <= "2026-06-30"),
    "dataset"
] = "VALIDATION"

df.loc[
    (df["record_date"] >= "2026-07-01") &
    (df["record_date"] <= "2026-07-20"),
    "dataset"
] = "TEST"

# Save prepared dataset
output_path = "tft/data/tft_dataset.csv"

df.to_csv(output_path, index=False)

print("\nTFT dataset created successfully!")
print("Saved to:", output_path)

print("\nDataset split:")
print(df.groupby("dataset").agg(
    first_date=("record_date", "min"),
    last_date=("record_date", "max"),
    total_days=("record_date", "count")
))

print("\nFinal columns:")
print(df.columns.tolist())