
import pandas as pd
import numpy as np
import torch

from pytorch_forecasting import (
    TimeSeriesDataSet,
    TemporalFusionTransformer
)

from sklearn.metrics import (
    mean_absolute_error,
    mean_squared_error,
    r2_score
)


# =====================================================
# SETTINGS
# =====================================================

DATA_PATH = "tft/data/tft_dataset.csv"
MODEL_PATH = "tft/models/tft_model_v6.ckpt"

MAX_ENCODER_LENGTH = 90
MAX_PREDICTION_LENGTH = 30

TEST_DAYS = 20
PADDING_DAYS = MAX_PREDICTION_LENGTH - TEST_DAYS


# =====================================================
# LOAD DATA
# =====================================================

print("Loading CarbonWise dataset...")

df = pd.read_csv(DATA_PATH)

df["record_date"] = pd.to_datetime(df["record_date"])

df = df.sort_values("time_idx").reset_index(drop=True)

df["series"] = "carbon_emissions"


# =====================================================
# CREATE V6 HISTORICAL FEATURES
# =====================================================

print("\nCreating V6 historical features...")

categories = [
    "electricity",
    "transportation",
    "food"
]

for category in categories:

    df[f"{category}_lag_1"] = (
        df[category].shift(1)
    )

    df[f"{category}_lag_7"] = (
        df[category].shift(7)
    )

    df[f"{category}_rolling_7"] = (
        df[category]
        .shift(1)
        .rolling(window=7, min_periods=7)
        .mean()
    )


feature_columns = [
    f"{category}_{feature}"
    for category in categories
    for feature in ["lag_1", "lag_7", "rolling_7"]
]

df = df.dropna(
    subset=feature_columns
).reset_index(drop=True)


# =====================================================
# CREATE TRAINING DATASET METADATA
# =====================================================

training_cutoff = df.loc[
    df["dataset"] == "TRAIN",
    "time_idx"
].max()

if pd.isna(training_cutoff):
    raise ValueError("No TRAIN records found.")

training_cutoff = int(training_cutoff)

training_df = df[
    df["time_idx"] <= training_cutoff
].copy()


unknown_reals = [
    "total_emission",
    "transportation",
    "electricity",
    "food",
    "emission_lag_1",
    "emission_lag_7",
    "emission_lag_14",
    "emission_rolling_7",
    "emission_rolling_30"
]

for category in categories:

    unknown_reals.extend([
        f"{category}_lag_1",
        f"{category}_lag_7",
        f"{category}_rolling_7"
    ])


known_reals = [
    "time_idx",
    "day_of_week",
    "month",
    "day_of_month",
    "is_weekend"
]


print("\nCreating V6 training metadata...")

training = TimeSeriesDataSet(
    training_df,

    time_idx="time_idx",

    target="total_emission",

    group_ids=["series"],

    min_encoder_length=MAX_ENCODER_LENGTH,
    max_encoder_length=MAX_ENCODER_LENGTH,

    min_prediction_length=MAX_PREDICTION_LENGTH,
    max_prediction_length=MAX_PREDICTION_LENGTH,

    static_categoricals=["series"],

    time_varying_known_reals=known_reals,

    time_varying_unknown_reals=unknown_reals,

    add_relative_time_idx=True,
    add_target_scales=True,
    add_encoder_length=True,

    allow_missing_timesteps=False,
)


# =====================================================
# IDENTIFY TEST DATA
# =====================================================

test_df = df[
    df["dataset"] == "TEST"
].copy()

test_df = test_df.sort_values(
    "record_date"
).reset_index(drop=True)


print("\n========================================")
print("V6 FINAL TEST DATA")
print("========================================")

print("Test records:", len(test_df))
print("First test date:", test_df["record_date"].min())
print("Last test date:", test_df["record_date"].max())


if len(test_df) != TEST_DAYS:
    raise ValueError(
        f"Expected {TEST_DAYS} test records, "
        f"found {len(test_df)}."
    )


expected_dates = pd.date_range(
    start=test_df["record_date"].min(),
    periods=TEST_DAYS,
    freq="D"
)

if not test_df["record_date"].reset_index(drop=True).equals(
    pd.Series(expected_dates)
):
    raise ValueError(
        "Test dates are not consecutive. "
        "Check the test dataset."
    )


# =====================================================
# PREPARE 30-DAY PREDICTION WINDOW
# =====================================================

print("\nPreparing 30-day prediction window...")

last_test_row = test_df.iloc[-1].copy()

padding_rows = []

for day_offset in range(1, PADDING_DAYS + 1):

    new_row = last_test_row.copy()

    new_date = (
        last_test_row["record_date"]
        + pd.Timedelta(days=day_offset)
    )

    new_row["record_date"] = new_date

    new_row["time_idx"] = (
        int(last_test_row["time_idx"]) + day_offset
    )

    new_row["day_of_week"] = new_date.dayofweek
    new_row["month"] = new_date.month
    new_row["day_of_month"] = new_date.day

    new_row["is_weekend"] = int(
        new_date.dayofweek >= 5
    )

    new_row["dataset"] = "TEST_PADDING"

    padding_rows.append(new_row)


padding_df = pd.DataFrame(padding_rows)


# These rows are placeholders only.
# They are not actual future observations.

prediction_df = pd.concat(
    [df, padding_df],
    ignore_index=True
)

prediction_df = prediction_df.sort_values(
    "time_idx"
).reset_index(drop=True)


print("Actual test days:", len(test_df))
print("Additional padding days:", len(padding_df))
print("Total prediction horizon:", MAX_PREDICTION_LENGTH)


# =====================================================
# SELECT 90-DAY ENCODER CONTEXT AND 30-DAY HORIZON
# =====================================================

first_test_time_idx = int(
    test_df["time_idx"].min()
)

last_padding_time_idx = int(
    padding_df["time_idx"].max()
)

minimum_time_idx = (
    first_test_time_idx - MAX_ENCODER_LENGTH
)

prediction_data = prediction_df[
    (prediction_df["time_idx"] >= minimum_time_idx)
    &
    (prediction_df["time_idx"] <= last_padding_time_idx)
].copy()


expected_total_rows = (
    MAX_ENCODER_LENGTH + MAX_PREDICTION_LENGTH
)

if len(prediction_data) != expected_total_rows:
    raise ValueError(
        f"Expected {expected_total_rows} rows, "
        f"found {len(prediction_data)}."
    )


print("\nPrediction data range:")

print(
    prediction_data[
        ["record_date", "time_idx", "dataset"]
    ].tail(MAX_PREDICTION_LENGTH).to_string(index=False)
)


# =====================================================
# LOAD V6 MODEL
# =====================================================

print("\nLoading trained TFT V6 model...")

model = TemporalFusionTransformer.load_from_checkpoint(
    MODEL_PATH,
    map_location="cpu"
)

model.eval()


# =====================================================
# NEW DIAGNOSTIC: CHECK CHECKPOINT CONFIGURATION
# =====================================================

print("\n========================================")
print("V6 CHECKPOINT VARIABLE CONFIGURATION")
print("========================================")

encoder_variables = (
    model.hparams.time_varying_reals_encoder
)

decoder_variables = (
    model.hparams.time_varying_reals_decoder
)

print("\nEncoder real variables:")

for variable in encoder_variables:
    print("-", variable)


print("\nDecoder real variables:")

for variable in decoder_variables:
    print("-", variable)


print("\nKnown future variables:")
print(known_reals)

print("\nUnknown future variables:")
print(unknown_reals)


# Check whether the decoder uses any unknown future variables.

decoder_unknown_variables = [
    variable
    for variable in decoder_variables
    if variable in unknown_reals
]

decoder_known_variables = [
    variable
    for variable in decoder_variables
    if variable in known_reals
]


print("\n========================================")
print("V6 DECODER CONFIGURATION RESULT")
print("========================================")

print("\nKnown variables used by decoder:")
print(decoder_known_variables)

print("\nUnknown variables found in decoder:")
print(decoder_unknown_variables)


if decoder_unknown_variables:

    print(
        "\nWARNING: Unknown future variables are present "
        "in the decoder configuration."
    )

else:

    print(
        "\nCONFIRMED: No unknown future variables "
        "are listed in the decoder configuration."
    )


# =====================================================
# GENERATE PREDICTION DATASET
# =====================================================

print("\n========================================")
print("GENERATING V6 TEST PREDICTIONS")
print("========================================")

prediction_dataset = TimeSeriesDataSet.from_dataset(
    training,
    prediction_data,
    predict=True,
    stop_randomization=True
)


# =====================================================
# DIAGNOSTIC: INSPECT ACTUAL DATASET INPUTS
# =====================================================

print("\n========================================")
print("V6 DATASET INPUT DIAGNOSTICS")
print("========================================")

print("Prediction sequences:", len(prediction_dataset))

if len(prediction_dataset) != 1:
    raise RuntimeError(
        "Expected exactly one prediction sequence. "
        f"Found {len(prediction_dataset)}."
    )


sample_x, sample_y = prediction_dataset[0]


print("\nAvailable input keys:")
print(list(sample_x.keys()))

print("\nInput object type:")
print(type(sample_x))

print("\nTarget structure:")
print(sample_y)


# =====================================================
# INSPECT ENCODER AND DECODER INPUT TENSOR
# =====================================================

print("\n========================================")
print("V6 ENCODER / DECODER INPUT INSPECTION")
print("========================================")


if "x_cont" not in sample_x:
    raise RuntimeError(
        "The expected x_cont input was not found."
    )


x_cont = sample_x["x_cont"]

encoder_length = int(sample_x["encoder_length"])
decoder_length = int(sample_x["decoder_length"])

real_variables = prediction_dataset.reals


print("\nFull continuous input shape:")
print(tuple(x_cont.shape))

print("\nEncoder length:", encoder_length)
print("Decoder length:", decoder_length)

print("\nNumber of real variables:", len(real_variables))

if x_cont.shape[1] != len(real_variables):
    raise RuntimeError(
        "Continuous input columns do not match "
        "the dataset real-variable list."
    )


encoder_cont = x_cont[:encoder_length]

decoder_cont = x_cont[
    encoder_length:encoder_length + decoder_length
]


print("\nEncoder continuous shape:")
print(tuple(encoder_cont.shape))

print("\nDecoder continuous shape:")
print(tuple(decoder_cont.shape))


print("\n========================================")
print("V6 VARIABLE CLASSIFICATION")
print("========================================")

print("\nKnown future variables:")
print(prediction_dataset.time_varying_known_reals)

print("\nUnknown future variables:")
print(prediction_dataset.time_varying_unknown_reals)


print("\n========================================")
print("V6 DECODER VARIABLE VALUES")
print("========================================")


for variable_index, variable in enumerate(real_variables):

    if variable in prediction_dataset.time_varying_unknown_reals:
        variable_type = "UNKNOWN"

    elif variable in prediction_dataset.time_varying_known_reals:
        variable_type = "KNOWN"

    else:
        variable_type = "OTHER"

    values = decoder_cont[:, variable_index]

    print(
        f"{variable_type:8} | "
        f"{variable:25} | "
        f"{values.detach().cpu().numpy()}"
    )


# =====================================================
# INSPECT DECODER TARGET
# =====================================================

print("\n========================================")
print("V6 DECODER TARGET INSPECTION")
print("========================================")


if isinstance(sample_y, tuple):

    decoder_target = sample_y[0]

else:

    decoder_target = sample_y


print("Decoder target shape:")
print(tuple(decoder_target.shape))

print("\nFirst 20 target values (actual test days):")
print(decoder_target[:TEST_DAYS])

print("\nLast 10 target values (padding days):")
print(decoder_target[TEST_DAYS:])


# =====================================================
# CHECK PREDICTION WINDOW DATES
# =====================================================

print("\n========================================")
print("PREDICTION WINDOW CHECK")
print("========================================")

print(
    prediction_data[
        ["record_date", "time_idx", "dataset"]
    ].tail(MAX_PREDICTION_LENGTH).to_string(index=False)
)


# =====================================================
# CREATE PREDICTION DATALOADER
# =====================================================

prediction_dataloader = prediction_dataset.to_dataloader(
    train=False,
    batch_size=1,
    num_workers=0
)


# =====================================================
# GENERATE PREDICTIONS
# =====================================================

print("\n========================================")
print("RUNNING TFT V6 PREDICTION")
print("========================================")

with torch.no_grad():

    prediction_output = model.predict(
        prediction_dataloader,
        mode="prediction"
    )


# =====================================================
# INSPECT RAW PREDICTION OUTPUT
# =====================================================

print("\n========================================")
print("RAW MODEL PREDICTION OUTPUT")
print("========================================")

print("Output type:", type(prediction_output))


if not torch.is_tensor(prediction_output):
    raise TypeError(
        "Expected a tensor prediction output. "
        f"Received {type(prediction_output)}."
    )


print("Raw output shape:")
print(tuple(prediction_output.shape))


predictions = (
    prediction_output
    .detach()
    .cpu()
    .numpy()
)


print("\nOriginal NumPy shape:")
print(predictions.shape)


# =====================================================
# EXTRACT THE 30-DAY FORECAST
# =====================================================

# Remove singleton dimensions.
# This handles shapes such as:
# (1, 1, 30)
# (1, 30, 1)
# (1, 30)

predictions = np.squeeze(predictions)


print("\nShape after removing singleton dimensions:")
print(predictions.shape)


if predictions.ndim != 1:
    raise RuntimeError(
        "Expected a one-dimensional prediction array "
        f"after squeezing, received {predictions.shape}."
    )


if len(predictions) != MAX_PREDICTION_LENGTH:
    raise RuntimeError(
        f"Expected {MAX_PREDICTION_LENGTH} predictions, "
        f"got {len(predictions)}. "
        f"Final shape: {predictions.shape}"
    )


print("\nSuccessfully extracted 30 predictions.")


# =====================================================
# EVALUATE ONLY THE 20 ACTUAL TEST DAYS
# =====================================================

test_predictions = predictions[:TEST_DAYS]

actuals = test_df[
    "total_emission"
].to_numpy()


if len(test_predictions) != len(actuals):
    raise RuntimeError(
        "Prediction and actual lengths do not match."
    )


# =====================================================
# CALCULATE FINAL TEST METRICS
# =====================================================

test_mae = mean_absolute_error(
    actuals,
    test_predictions
)

test_rmse = np.sqrt(
    mean_squared_error(
        actuals,
        test_predictions
    )
)

test_r2 = r2_score(
    actuals,
    test_predictions
)


print("\n========================================")
print("TFT V6 FINAL TEST RESULTS")
print("========================================")

print("Test days evaluated:", len(actuals))

print(f"MAE:  {test_mae:.2f}")
print(f"RMSE: {test_rmse:.2f}")
print(f"R2:   {test_r2:.4f}")


# =====================================================
# SAVE TEST PREDICTIONS
# =====================================================

results = pd.DataFrame({
    "record_date": test_df["record_date"],
    "actual_emission": actuals,
    "predicted_emission": test_predictions
})

results["prediction_error"] = (
    results["predicted_emission"]
    - results["actual_emission"]
)


output_path = (
    "tft/data/tft_v6_test_predictions.csv"
)

results.to_csv(
    output_path,
    index=False
)


print("\nTest predictions saved to:")
print(output_path)


# =====================================================
# SAVE TEST METRICS
# =====================================================

metrics = pd.DataFrame([{
    "model": "TFT V6",
    "test_days": len(actuals),
    "mae": test_mae,
    "rmse": test_rmse,
    "r2": test_r2
}])


metrics_path = (
    "tft/data/tft_v6_test_metrics.csv"
)

metrics.to_csv(
    metrics_path,
    index=False
)


print("\nTest metrics saved to:")
print(metrics_path)


# =====================================================
# DISPLAY PREDICTIONS
# =====================================================

print("\nActual vs Predicted Test Emissions:")

print(
    results.to_string(index=False)
)


print("\n========================================")
print("V6 FINAL TEST EVALUATION COMPLETED")
print("========================================")
