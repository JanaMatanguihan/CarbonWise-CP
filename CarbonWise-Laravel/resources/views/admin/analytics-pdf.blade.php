<!DOCTYPE html>
<html>
<head>

    <title>CarbonWise Analytics Report</title>

    <style>

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
        }


        .header {
            text-align:center;
            margin-bottom:30px;
        }


        h1 {
            color:#166534;
            margin-bottom:5px;
        }


        table {

            width:100%;
            border-collapse:collapse;
            margin-top:20px;

        }


        th {

            background:#166534;
            color:white;
            padding:10px;
            font-size:12px;

        }


        td {

            border:1px solid #ddd;
            padding:8px;
            font-size:11px;

        }


        .footer {

            margin-top:30px;
            font-size:11px;
            text-align:center;
            color:#666;

        }


    </style>


</head>


<body>


<div class="header">


    <h1>
        CarbonWise Analytics Report
    </h1>


    <p>
        Generated Report
    </p>


</div>



<table>


<thead>

<tr>

    <th>User</th>

    <th>Transportation</th>

    <th>Electricity</th>

    <th>Food</th>

    <th>Waste</th>

    <th>Total Emission</th>

    <th>Date</th>


</tr>


</thead>



<tbody>


@foreach($records as $record)


<tr>


<td>

    {{ $record->g_suite }}

</td>



<td>

    {{ $record->transportation }}

</td>



<td>

    {{ $record->electricity }}

</td>



<td>

    {{ $record->food }}

</td>



<td>

    {{ $record->waste }}

</td>



<td>

    {{ $record->total_emission }}

</td>



<td>

    {{ $record->record_date }}

</td>



</tr>


@endforeach


</tbody>


</table>



<div class="footer">

    CarbonWise Sustainability Monitoring System

</div>



</body>
</html>