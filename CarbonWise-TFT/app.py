from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
import pandas as pd
import torch

from pytorch_forecasting import TemporalFusionTransformer, TimeSeriesDataSet

app = FastAPI()

MODEL_PATH = "models/tft_model_v2.ckpt"


class ForecastRequest(BaseModel):
    records: list[dict]


@app.get("/")
def home():
    return {"message": "CarbonWise TFT Forecast API is running"}


@app.post("/forecast")
def get_forecast(request: ForecastRequest):
    try:
        # ---------------------------------------------------------
        # Convert Laravel records to DataFrame
        # ---------------------------------------------------------
        df = pd.DataFrame(request.records)

        if len(df) < 90:
            raise HTTPException(
                status_code=422,
                detail="At least 90 carbon records are required."
            )

        # ---------------------------------------------------------
        # Prepare data
        # ---------------------------------------------------------
        df["record_date"] = pd.to_datetime(df["record_date"])

        df = df.sort_values("record_date").reset_index(drop=True)

        numeric_columns = [
            "transportation",
            "electricity",
            "food",
            "total_emission",
        ]

        for column in numeric_columns:
            df[column] = pd.to_numeric(
                df[column],
                errors="coerce"
            ).astype(float)

        if df[numeric_columns].isnull().any().any():
            raise HTTPException(
                status_code=422,
                detail="Some carbon emission values are invalid."
            )

        # ---------------------------------------------------------
        # Add features used during training
        # ---------------------------------------------------------
        df["series"] = "carbon_emissions"

        df["time_idx"] = range(len(df))
        df["time_idx"] = df["time_idx"].astype(int)

        df["day_of_week"] = df["record_date"].dt.dayofweek
        df["month"] = df["record_date"].dt.month
        df["day_of_month"] = df["record_date"].dt.day

        df["is_weekend"] = (
            df["day_of_week"] >= 5
        ).astype(int)

        # Lag features
        df["emission_lag_1"] = (
            df["total_emission"].shift(1)
        )

        df["emission_lag_7"] = (
            df["total_emission"].shift(7)
        )

        df["emission_lag_14"] = (
            df["total_emission"].shift(14)
        )

        # Rolling features
        df["emission_rolling_7"] = (
            df["total_emission"]
            .rolling(7)
            .mean()
        )

        df["emission_rolling_30"] = (
            df["total_emission"]
            .rolling(30)
            .mean()
        )

        # Fill initial missing lag/rolling values
        df = df.bfill()

        feature_columns = [
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

        for column in feature_columns:
            df[column] = df[column].astype(float)

        # ---------------------------------------------------------
        # Load trained TFT model
        # ---------------------------------------------------------
        model = TemporalFusionTransformer.load_from_checkpoint(
            MODEL_PATH,
            map_location="cpu"
        )

        model.eval()

        dataset_parameters = model.hparams.dataset_parameters

        # ---------------------------------------------------------
        # Get latest 90 records as history
        # ---------------------------------------------------------
        history = df.tail(90).copy()

        prediction_length = 30

        # ---------------------------------------------------------
        # Create future dates
        # ---------------------------------------------------------
        future_dates = pd.date_range(
            start=history["record_date"].max()
            + pd.Timedelta(days=1),
            periods=prediction_length,
            freq="D"
        )

        future_df = pd.DataFrame({
            "record_date": future_dates
        })

        # Continue time index
        future_df["time_idx"] = range(
            int(history["time_idx"].max()) + 1,
            int(history["time_idx"].max()) + 1
            + prediction_length
        )

        # Calendar features
        future_df["day_of_week"] = future_dates.dayofweek
        future_df["month"] = future_dates.month
        future_df["day_of_month"] = future_dates.day

        future_df["is_weekend"] = (
            future_dates.dayofweek >= 5
        ).astype(int)

        # Same series
        future_df["series"] = "carbon_emissions"

        # ---------------------------------------------------------
        # Fill required future columns
        # ---------------------------------------------------------
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

        last_values = history.iloc[-1]

        for column in unknown_columns:
            future_df[column] = float(
                last_values[column]
            )

        for column in unknown_columns:
            future_df[column] = future_df[column].astype(float)

        future_df["time_idx"] = (
            future_df["time_idx"].astype(int)
        )

        # ---------------------------------------------------------
        # Combine history + future
        # ---------------------------------------------------------
        forecast_input = pd.concat(
            [history, future_df],
            ignore_index=True
        )

        # ---------------------------------------------------------
        # Create dataset AFTER adding future rows
        # ---------------------------------------------------------
        base_dataset = TimeSeriesDataSet(
            forecast_input,
            time_idx=dataset_parameters["time_idx"],
            target=dataset_parameters["target"],
            group_ids=dataset_parameters["group_ids"],
            min_encoder_length=dataset_parameters["min_encoder_length"],
            max_encoder_length=dataset_parameters["max_encoder_length"],
            min_prediction_length=dataset_parameters["min_prediction_length"],
            max_prediction_length=dataset_parameters["max_prediction_length"],
            static_categoricals=dataset_parameters["static_categoricals"],
            time_varying_known_reals=dataset_parameters[
                "time_varying_known_reals"
            ],
            time_varying_unknown_reals=dataset_parameters[
                "time_varying_unknown_reals"
            ],
            allow_missing_timesteps=dataset_parameters[
                "allow_missing_timesteps"
            ],
            add_relative_time_idx=dataset_parameters[
                "add_relative_time_idx"
            ],
            add_target_scales=dataset_parameters[
                "add_target_scales"
            ],
            add_encoder_length=dataset_parameters[
                "add_encoder_length"
            ],
            target_normalizer=dataset_parameters[
                "target_normalizer"
            ],
        )

        # ---------------------------------------------------------
        # Create prediction dataset
        # ---------------------------------------------------------
        prediction_dataset = TimeSeriesDataSet.from_dataset(
            base_dataset,
            forecast_input,
            predict=True,
            stop_randomization=True
        )

        # ---------------------------------------------------------
        # Create DataLoader
        # ---------------------------------------------------------
        prediction_loader = prediction_dataset.to_dataloader(
            train=False,
            batch_size=1,
            num_workers=0
        )

        # ---------------------------------------------------------
        # Generate forecast
        # ---------------------------------------------------------
        with torch.no_grad():
            predictions = model.predict(
                prediction_loader
            )

        forecast_values = (
            predictions[0]
            .detach()
            .cpu()
            .numpy()
        )

        # ---------------------------------------------------------
        # Build response
        # ---------------------------------------------------------
        forecast = []

        for i in range(prediction_length):
            forecast.append({
                "record_date": future_dates[i].strftime(
                    "%Y-%m-%d"
                ),
                "predicted_total_emission": float(
                    forecast_values[i]
                )
            })

        return {
            "forecast": forecast
        }

    except HTTPException:
        raise

    except Exception as e:
        print("TFT ERROR:", str(e))

        raise HTTPException(
            status_code=500,
            detail=str(e)
        )