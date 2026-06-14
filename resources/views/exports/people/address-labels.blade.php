<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Address Labels</title>
    <style>
        @page {
            size: letter;
            margin: 0.5in 0.1875in;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            background: #ffffff;
            color: #000000;
            font-family: Arial, Helvetica, sans-serif;
        }

        .missing-addresses {
            page: missing-addresses;
            font-size: 11pt;
            line-height: 1.35;
        }

        .missing-addresses h1 {
            font-size: 16pt;
            margin: 0 0 0.15in;
        }

        .missing-addresses p {
            margin: 0 0 0.15in;
        }

        .missing-addresses ul {
            margin: 0;
            padding-left: 0.25in;
        }

        .missing-addresses li {
            margin-bottom: 0.05in;
        }

        .labels-section.after-missing {
            break-before: page;
        }

        .sheet {
            break-after: page;
            display: grid;
            grid-auto-rows: 1in;
            grid-template-columns: repeat(3, 2.625in);
            column-gap: 0.125in;
            row-gap: 0;
            width: 8.125in;
        }

        .sheet:last-child {
            break-after: auto;
        }

        .label {
            align-items: center;
            display: flex;
            height: 1in;
            overflow: hidden;
            padding: 0.1in 0.12in;
            width: 2.625in;
        }

        .address {
            font-size: 10pt;
            line-height: 1.2;
            width: 100%;
        }

        .address div {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .empty {
            padding: 1rem;
        }

        @page missing-addresses {
            size: letter;
            margin: 0.75in;
        }

        @media screen {
            body {
                background: #e5e7eb;
                padding: 0.25in;
            }

            .sheet {
                background: #ffffff;
                box-shadow: 0 1px 8px rgba(0, 0, 0, 0.15);
                margin: 0 auto 0.25in;
            }

            .missing-addresses {
                background: #ffffff;
                box-shadow: 0 1px 8px rgba(0, 0, 0, 0.15);
                margin: 0 auto 0.25in;
                max-width: 8.5in;
                padding: 0.75in;
            }
        }

        @media print {
            .empty {
                display: none;
            }
        }
    </style>
</head>
<body>
@if (count($missingAddresses) > 0)
    <section class="missing-addresses">
        <h1>Missing Addresses</h1>
        <p>These filtered records do not have a complete mailing address and were not printed on labels.</p>
        <ul>
            @foreach ($missingAddresses as $missingAddress)
                <li>
                    {{ $missingAddress['name'] }}
                    @if ($missingAddress['details'] !== '')
                        - {{ $missingAddress['details'] }}
                    @endif
                </li>
            @endforeach
        </ul>
    </section>
@endif

@if (count($labels) > 0)
    <div class="labels-section {{ count($missingAddresses) > 0 ? 'after-missing' : '' }}">
        @foreach (array_chunk($labels, 30) as $sheet)
            <div class="sheet">
                @foreach ($sheet as $label)
                    <div class="label">
                        <div class="address">
                            @foreach ($label['lines'] as $line)
                                <div>{{ $line }}</div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
@else
    <div class="empty">
        @if (count($missingAddresses) > 0)
            No complete addresses matched the current filters.
        @else
            No labels matched the current filters.
        @endif
    </div>
@endif
</body>
</html>
