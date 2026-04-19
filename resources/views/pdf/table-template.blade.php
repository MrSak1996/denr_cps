<!DOCTYPE html>
<html>

<head>
    <title>Permit to Purchase Chainsaw</title>
    <style>
        @page {
            size: A4;
            margin: 2.54cm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            margin: 0;
            padding: 0;
        }

        /* Header full width */
        .header-full {
            position: absolute;
            top: -2.54cm;
            left: -2.54cm;
            right: -2.54cm;
            width: calc(100% + 5.08cm);
        }
        .footer-full {
            position: absolute;
            bottom: -10cm;
            left: -2.54cm;
            right: -2.54cm;
            width: calc(100% + 5.08cm);
        }

        /* Watermark */
        body::before {
            content: "";
            position: absolute;
            top: 40%;
            left: 50%;
            width: 90%;
            height: 90%;
            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;
            opacity: 0.25;
            transform: translate(-50%, -50%);
            z-index: -1;
        }

        /* Title */
        .title-section h2 {
            font-size: 22px;
            font-weight: bold;
        }

        /* Table */
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 0;
            vertical-align: top;
            line-height: 1.2;
        }

        /* Form Layout */
        .label {
            width: 0px;
            font-weight: bold;
            vertical-align: top;
        }

        .colon {
            width: 5px;
            text-align: center;
            vertical-align: top;
        }

        .value {
            width: auto;
            font-weight: normal;
            text-decoration: underline;
            white-space: normal;
        }

        /* Field underline */
        .field {
            display: inline;
            border-bottom: 1px solid black;
        }

        /* Wrapping fields */
        .wrap {
            white-space: normal;
            word-wrap: break-word;

        }

        /* Signature */
        .signature {
            margin-top: 20px;
            text-align: right;
        }
    </style>
</head>

<body>



    <div class="header-container">
        <!-- HEADER -->
        <div class="header-full">

            <table style="width:100%; border-collapse:collapse;">

                <tr>

                    <td style="width:130px; text-align:right !important;">
                        <img src="{{ public_path('images/denr_logo.png') }}" style="width:95px;">
                    </td>

                    <td style="text-align:center; font-family:'Times New Roman'; line-height:1.2;">

                        <div style="font-size:13pt;">
                            Republic of the Philippines
                        </div>

                        <div style="font-size:12pt; font-weight:bold;">
                            <span style="font-size:18pt;">D</span>EPARTMENT of
                            <span style="font-size:18pt;">E</span>NVIRONMENT and
                            <span style="font-size:18pt;">N</span>ATURAL
                            <span style="font-size:18pt;">R</span>ESOURCES
                        </div>

                        <div style="font-size:12pt;font-weight:bold;">
                            REGION IV-A | CALABARZON
                        </div>

                    </td>

                    <td style="width:130px; text-align:left !important;">
                        <img src="{{ public_path('images/bp.png') }}" style="width:95px;">
                    </td>

                </tr>

            </table>

        </div>

        <!-- Red Line -->
        <!-- <div style="position:fixed;z-index:1000; width:100%; height:10px; background:#8D1010; margin-top:8px;"></div> -->
    </div>




    <!-- TITLE -->
    <div style="text-align:center; margin-top:10px; margin-bottom:20px;">

        <div style="font-weight:bold; font-size:15pt;">
            PERMIT TO PURCHASE CHAINSAW
        </div>

        <div style="font-size:13pt; margin-top:3px;font-weight:bold;">
            NO. <span style="text-decoration:underline;">{{ $permit_number }}</span>
        </div>

    </div>

    <!-- BODY TEXT -->
    <p style="text-align: justify;font-size:12pt !important;">
        Pursuant to the provisions of DENR Administrative Order No. 2003-24, Series of 2003 which provides the
        "Implementing Guidelines of R.A. 9175 of 2002" entitled "An Act Regulating the Possession, Ownership,
        Sale, Importation and Use of Chainsaws penalizing violations thereof and for other related purposes" and
        Department Administrative Order No. 2022-10 re: Revised DENR Manual of Authorities on Technical Matters
        dated May 30, 2022, this <b>PERMIT TO PURCHASE CHAINSAW </b>is hereby issued to:
    </p>

    <table class="info-table">
        <tr>
            <td style="width: 90px;">Name:</td>
            <td style="font-weight:bold;"><span class="field wrap">{{ $name }}</span></td>
        </tr>
        <tr>
            <td>Address:</td>
            <td style="font-weight:bold;"><span class="field">{{ $complete_address }}</span>
            </td>
        </tr>
    </table>

    <p>The following information and descriptions of the chainsaw subject of this permit are
        hereby enumerated:</p>

    <table class="info-table chainsaw-details" style="width:100%; font-size:11pt !important; border-collapse:collapse;">
        <tr>
            <td class="label">Quantity</td>
            <td class="colon">:</td>
            <td class="value"><span class="field">{{ $quantity }}</span></td>
        </tr>

        @foreach($brands as $brand)
        <tr>
            <td class="label">Brand</td>
            <td class="colon">:</td>
            <td class="value"><span class="field">{{ $brand['brand_name'] }}</span></td>
        </tr>

        <tr>
            <td class="label">Model</td>
            <td class="colon">:</td>
            <td class="value">
                <span class="field">
                    {{ $brand['models']->map(fn($m) => $m['model'])->implode(', ') }} ({{ $model_qty }})
                </span>
            </td>
        </tr>
        @endforeach

        <tr>
            <td class="label">Serial No.</td>
            <td class="colon">:</td>
            <td class="value"><span class="field">{{ $engine_serial_no }}</span></td>
        </tr>

        <!-- Fixed Source of Chainsaw/Suppliers -->
        <tr>
            <td class="label" colspan="2" style="font-weight:bold; vertical-align: top; width: 185px;">
                Source of Chainsaw/Suppliers
            </td>
            <td class="colon">:</td>
        </tr>
        <tr>
            <td colspan="2"></td>
            <td class="value" style="text-decoration: underline; font-weight: normal; white-space: normal;">
                {{ $supplier_name }} with business address at <br>
                {{ $supplier_address }}
            </td>
        </tr>

        <tr>
            <td class="label">Purpose</td>
            <td class="colon">:</td>
            <td class="value"><span class="field">{{ $purpose }}</span></td>
        </tr>

        <tr>
            <td class="label">Others</td>
            <td class="colon">:</td>
            <td class="value">
                <span class="field wrap">
                    Covered by Permit to Sell Chainsaw No.
                    <b>{{ $permit_chainsaw_no }}</b>
                    issued on {{ $issued_date }},
                    valid until {{ $permit_validity }},
                    issued by {{ $issued_by }}
                </span>
            </td>
        </tr>
    </table>


    <p style="margin-top: 20px;">
        Issued on
        <span style="text-decoration:underline;">
            {{ now()->format('F d, Y') }}
        </span>
        in Brgy. Mayapa, Calamba City Laguna<br>
        Expiry Date: <span style="text-decoration:underline;">{{ $expiry_date }}</span>
    </p>

    <!-- SIGNATURE + FEES SECTION -->
    <div style="margin-top:40px; font-size:12pt; width:100%; position:relative;">

        <!-- APPROVAL BLOCK -->
        <div style="text-align:right; margin-bottom:5px; position:relative;">

            <p style="margin:0 95 0 0; font-weight:bold;">Approved:</p>

            <!-- Floating Signature -->
            <img src="{{ storage_path('app/private/signatures/red_signature.png') }}"
                style="
            position:absolute;
            right:60px;
            top:-5px;
            width:120px;
            opacity:0.9;
            z-index:10;
         ">

            <p style="margin:35px 0 0 0; font-weight:bold;">
                NILO B. TAMORIA, CESO III
            </p>

            <p style="margin:0;">
                Regional Executive Director
            </p>

            <p style="margin-top:8px; font-size:10pt; font-style:italic;">
                Generated on: {{ now()->format('F d, Y H:i:s') }}
            </p>

        </div>

        <!-- FEES BLOCK -->
        <div style="font-size:12pt;">
            <p style="margin:0;">
                Permit Fee: P500.00<br>
                O.R. No.: <span class="field">{{ $or_number }}</span><br>
                Date: <span class="field">{{ $or_date }}</span>
            </p>
        </div>


    </div>



    <div class="footer-full">

        <table style="width:100%; border-collapse:collapse;" border=1>

            <tr>

                <td style="width:130px">
                    <img src="{{ public_path('images/iso.png') }}" style="width:60px;">
                </td>

                <td style="text-align:center;">

                    <div style="font-weight:bold;">
                        DENR IV-A (CALABARZON) Compound, Mayapa Main Road (along SLEX)
                    </div>

                    <div>
                        Barangay Mayapa, Calamba City, Laguna
                    </div>

                    <div>
                        Trunkline: (049) 540-DENR (3367)
                    </div>

                    <div>
                        Email: r4a@denr.gov.ph | calabarzon.denr.gov.ph
                    </div>

                </td>

                <td style="width:13px; text-align:right;">
                    <img src="{{ public_path('images/iso2.jpg') }}" style="width:150px;height:100px">
                </td>

            </tr>

        </table>

    </div>


</body>

</html>