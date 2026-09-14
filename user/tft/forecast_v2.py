from datetime import datetime, timedelta
import numpy as np
import pandas as pd
import psycopg2
import torch
from pytorch_forecasting import TemporalFusionTransformer, TimeSeriesDataSet

print("Forecast V2 script started")

# Neon PostgreSQL Database Configuration
DB_HOST = "ep-red-hill-a5erg1sb-pooler.us-east-2.aws.neon.tech"
DB_NAME = "neondb"
DB_USER = "neondb_owner"
DB_PASS = "npg_B7h4oEQbqJdG"
DB_PORT = "5432"

# File paths
MODEL_PATH = "tft/models/tft_model_v2.ckpt"
DATA_PATH = "tft/data/tft_dataset.csv"

print("Model path:", MODEL_PATH)
print("Data path:", DATA_PATH)

# Load V2 dataset
df = pd.read_csv(DATA_PATH)

# Convert dates to datetime
df["record_date"] = pd.to_datetime(df["record_date"])

# Add the series identifier required by the trained TFT model
df["series"] = "carbon_emissions"

print("Dataset loaded")
print("Rows:", len(df))
print("Date range:", df["record_date"].min().date(), "to", df["record_date"].max().date())

# Load the trained V2 TFT model
model = TemporalFusionTransformer.load_from_checkpoint(
    MODEL_PATH,
    map_location="cpu"
)

print("V2 model loaded successfully")

# Recreate the dataset configuration used by V2
dataset_parameters = model.hparams.dataset_parameters

print("Dataset configuration loaded")
print("Target:", dataset_parameters["target"])
print("Encoder length:", dataset_parameters["max_encoder_length"])
print("Prediction length:", dataset_parameters["max_prediction_length"])

# Create the TimeSeriesDataSet using the saved V2 configuration
forecast_dataset = TimeSeriesDataSet(
    df,
    time_idx=dataset_parameters["time_idx"],
    target=dataset_parameters["target"],
    group_ids=dataset_parameters["group_ids"],
    min_encoder_length=dataset_parameters["min_encoder_length"],
    max_encoder_length=dataset_parameters["max_encoder_length"],
    min_prediction_length=dataset_parameters["min_prediction_length"],
    max_prediction_length=dataset_parameters["max_prediction_length"],
    static_categoricals=dataset_parameters["static_categoricals"],
    time_varying_known_reals=dataset_parameters["time_varying_known_reals"],
    time_varying_unknown_reals=dataset_parameters["time_varying_unknown_reals"],
    allow_missing_timesteps=dataset_parameters["allow_missing_timesteps"],
    add_relative_time_idx=dataset_parameters["add_relative_time_idx"],
    add_target_scales=dataset_parameters["add_target_scales"],
    add_encoder_length=dataset_parameters["add_encoder_length"],
    target_normalizer=dataset_parameters["target_normalizer"],
)

print("TimeSeriesDataSet created successfully")

# Use the latest available data for the forecast
latest_time_idx = df["time_idx"].max()

prediction_data = df[
    df["time_idx"] > latest_time_idx - dataset_parameters["max_encoder_length"]
].copy()

print("Prediction data prepared")
print("Rows:", len(prediction_data))
print(
    "Prediction history:",
    prediction_data["record_date"].min().date(),
    "to",
    prediction_data["record_date"].max().date()
)

# Create the 30-day future forecast dates
prediction_length = dataset_parameters["max_prediction_length"]

future_dates = pd.date_range(
    start=df["record_date"].max() + pd.Timedelta(days=1),
    periods=prediction_length,
    freq="D"
)

print("Future forecast dates created")
print("Forecast start:", future_dates.min().date())
print("Forecast end:", future_dates.max().date())
print("Forecast days:", len(future_dates))

# Build the future calendar features required by V2
future_df = pd.DataFrame({
    "record_date": future_dates
})

future_df["time_idx"] = range(
    int(df["time_idx"].max()) + 1,
    int(df["time_idx"].max()) + 1 + prediction_length
)

future_df["day_of_week"] = future_df["record_date"].dt.dayofweek
future_df["month"] = future_df["record_date"].dt.month
future_df["day_of_month"] = future_df["record_date"].dt.day
future_df["is_weekend"] = (
    future_df["day_of_week"] >= 5
).astype(int)

future_df["series"] = "carbon_emissions"

print("Future calendar features prepared")
print(
    future_df[
        [
            "record_date",
            "time_idx",
            "day_of_week",
            "month",
            "day_of_month",
            "is_weekend"
        ]
    ].head().to_string(index=False)
)

# Combine historical window with forecast window
history_df = prediction_data.copy()
future_input = future_df.copy()

unknown_columns = [
    "total_emission",
    "transportation",
    "electricity",
    "food",
    "emission_lag_1",
    "emission_lag_7",
    "emission_lag_14",
    "emission_rolling_7",
    "emission_rolling_30",
]

for column in unknown_columns:
    future_input[column] = df[column].iloc[-1]

forecast_input = pd.concat(
    [history_df, future_input],
    ignore_index=True
)

print("Forecast input prepared")
print("Total rows:", len(forecast_input))
print(
    "Forecast input range:",
    forecast_input["record_date"].min().date(),
    "to",
    forecast_input["record_date"].max().date()
)

# Create the final prediction dataset
prediction_dataset = TimeSeriesDataSet.from_dataset(
    forecast_dataset,
    forecast_input,
    predict=True,
    stop_randomization=True
)

print("Final prediction dataset created")
print("Prediction samples:", len(prediction_dataset))

# Create a DataLoader for the final forecast
prediction_loader = prediction_dataset.to_dataloader(
    train=False,
    batch_size=1,
    num_workers=0
)

print("Prediction DataLoader created")
print("Batches:", len(prediction_loader))

# Generate the 30-day forecast
model.eval()

with torch.no_grad():
    predictions = model.predict(prediction_loader)

print("Forecast generated successfully")
print("Prediction shape:", predictions.shape)

# Extract forecast values
forecast_values = predictions[0].detach().cpu().numpy()

print("\nFORECAST VALUES:")
for date, value in zip(future_dates, forecast_values):
    print(f"{date.date()} -> {value:.2f}")

# Save the forecast to CSV (Existing output)
forecast_output = pd.DataFrame({
    "record_date": future_dates,
    "predicted_total_emission": forecast_values
})

OUTPUT_PATH = "tft/data/tft_v2_forecast.csv"
forecast_output.to_csv(OUTPUT_PATH, index=False)

print("\nForecast saved to CSV:", OUTPUT_PATH)

# ==============================================================================
# POSTGRESQL DATABASE SYNC (NEON DB)
# ==============================================================================
def sync_forecast_to_neon(dates, values, target_user_id=1):
    print("\nConnecting to Neon PostgreSQL Database...")
    try:
        conn = psycopg2.connect(
            host=DB_HOST,
            dbname=DB_NAME,
            user=DB_USER,
            password=DB_PASS,
            port=DB_PORT,
            sslmode="require"
        )
        cur = conn.cursor()

        # 1. Clear previous future predictions for the user
        cur.execute(
            "DELETE FROM forecast_records WHERE user_id = %s AND forecast_date >= CURRENT_DATE;",
            (target_user_id,)
        )

        # 2. Insert new prediction records
        insert_query = """
            INSERT INTO forecast_records (user_id, forecast_date, predicted_emission)
            VALUES (%s, %s, %s);
        """
        records_to_insert = [
            (target_user_id, date.date(), float(val))
            for date, val in zip(dates, values)
        ]

        cur.executemany(insert_query, records_to_insert)
        conn.commit()

        print(f"Successfully inserted {len(records_to_insert)} records into 'forecast_records' for User ID {target_user_id}!")

        cur.close()
        conn.close()

    except Exception as e:
        print("Failed to sync forecasts to Neon DB:", e)

# Execute DB insertion
sync_forecast_to_neon(future_dates, forecast_values, target_user_id=1)