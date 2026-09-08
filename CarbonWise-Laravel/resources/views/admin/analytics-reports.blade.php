@extends('layouts.admin')

@section('page-title', 'Analytics and Report')
@section('page-subtitle', 'Generate reports and gain insights')


@section('content')


<div class="bg-[#f1f1ee] min-h-screen p-8 -mx-6 -mt-6 space-y-6">





    <!-- Summary Report -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">



        <h3 class="font-bold text-lg">

            Summary Report

        </h3>



        <p class="text-gray-400 text-sm mb-8">

            Overview of overall emissions and user engagement

        </p>






        <div class="grid grid-cols-5 gap-8 items-center">






            <!-- Total Users -->
            <div class="flex items-center gap-4">


                <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center">

                    <img
                        src="{{ asset('icons/user.png') }}"
                        class="w-8 h-8"
                    >

                </div>



                <div>

                    <p class="text-xs text-gray-500">
                        Total Users
                    </p>


                    <h2 class="font-bold text-xl">

                        {{ number_format($totalUsers) }}

                    </h2>


                </div>



            </div>









            <!-- Total Emissions -->
            <div class="flex items-center gap-4">


                <div class="w-16 h-16 rounded-full bg-cyan-100 flex items-center justify-center">


                    <img
                        src="{{ asset('icons/emissions.png') }}"
                        class="w-8 h-8"
                    >


                </div>




                <div>


                    <p class="text-xs text-gray-500">

                        Total Emissions

                    </p>



                    <h2 class="font-bold text-xl">


                        {{ number_format($totalEmissions,2) }}


                        <span class="text-xs">

                            kg CO₂e

                        </span>


                    </h2>


                </div>



            </div>










            <!-- Average -->
            <div class="flex items-center gap-4">



                <div class="w-16 h-16 rounded-full bg-yellow-100 flex items-center justify-center">


                    <img
                        src="{{ asset('icons/analytics.png') }}"
                        class="w-8 h-8"
                    >


                </div>





                <div>


                    <p class="text-xs text-gray-500">

                        Avg Emission per User

                    </p>



                    <h2 class="font-bold text-xl">

                        {{ number_format($averageEmission,2) }}

                    </h2>



                </div>



            </div>










            <!-- Active Users -->
            <div class="flex items-center gap-4">


                <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center">


                    <img
                        src="{{ asset('icons/user.png') }}"
                        class="w-8 h-8"
                    >


                </div>





                <div>


                    <p class="text-xs text-gray-500">

                        Active Users

                    </p>


                    <h2 class="font-bold text-xl">

                        {{ number_format($activeUsers) }}

                    </h2>


                </div>




            </div>









            <!-- Mitigation -->
            <div class="flex items-center gap-4">


                <div class="w-16 h-16 rounded-full bg-green-700 flex items-center justify-center">


                    <img
                        src="{{ asset('icons/mitigation.png') }}"
                        class="w-8 h-8"
                    >


                </div>





                <div>


                    <p class="text-xs text-gray-500">

                        Mitigation Actions

                    </p>


                    <h2 class="font-bold text-xl">

                        {{ number_format($mitigationActions) }}

                    </h2>



                </div>



            </div>




        </div>




    </div>












    <!-- Top Emitting Sources -->
    <div class="bg-white rounded-xl shadow-sm border p-8">



        <h3 class="font-bold mb-8">

            Top Emitting Sources

        </h3>






        <div class="space-y-6">





            @foreach($sources as $name => $value)




            @php


            $percentage =
            $highestSource > 0
            ? ($value / $highestSource) * 100
            : 0;


            @endphp






            <div class="grid grid-cols-[220px_1fr_70px] items-center">



                <span class="text-sm">

                    {{ $name }}

                </span>






                <div class="h-3 bg-gray-200 rounded-full overflow-hidden">


                    <div

                        class="bg-green-700 h-full rounded-full"

                        @style([

                            "width: {$percentage}%"

                        ])

                    ></div>


                </div>






                <span class="text-xs text-gray-500 text-right">


                    {{ number_format($percentage,1) }}%


                </span>




            </div>





            @endforeach




        </div>





    </div>












    <!-- Chart -->
    <div class="bg-white rounded-xl shadow-sm border p-8">



        <h3 class="font-bold mb-5">

            Emissions Comparison

        </h3>



        <div id="comparisonChart"></div>




    </div>





</div>









@push('scripts')

<script>


const comparisonData = JSON.parse('@json($comparisonData)');



new ApexCharts(


document.querySelector("#comparisonChart"),


{


chart:{


type:'bar',

height:350,

toolbar:false


},




series:[


{

name:'This Month',

data:comparisonData.current

},


{

name:'Last Month',

data:comparisonData.last

}


],






xaxis:{


categories:[

'Transportation',

'Electricity',

'Food Consumption',

'Others'

]


},






colors:[

'#4f8b3a',

'#9ca3af'

],






plotOptions:{


bar:{


borderRadius:4,

columnWidth:'45%'


}


}



}



).render();


</script>


@endpush




@endsection