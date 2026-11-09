{{-- Kalkulator pop up: data contoh statis (tiga besaran, tiga mata uang; EUR sengaja tanpa kurs untuk memperlihatkan tandanya). --}}
<div class="dl-demo dl-demo--row" style="background:var(--dl-navy);padding:16px;border-radius:var(--dl-radius-lg)">
    <x-dl::calculator id="demo-calculator" storage-key="dl-calculator-demo" rate-url="#" :featured="['LENGTH', 'MASS', 'TEMPERATURE']"
        :quantities="[
            ['key' => 'LENGTH', 'label' => 'Length', 'units' => [['c' => 'mm', 'n' => 'millimetre', 'f' => 0.001, 'o' => 0], ['c' => 'm', 'n' => 'metre', 'f' => 1, 'o' => 0], ['c' => 'in', 'n' => 'inch', 'f' => 0.0254, 'o' => 0]]],
            ['key' => 'MASS', 'label' => 'Weight', 'units' => [['c' => 'g', 'n' => 'gram', 'f' => 0.001, 'o' => 0], ['c' => 'kg', 'n' => 'kilogram', 'f' => 1, 'o' => 0]]],
            ['key' => 'TEMPERATURE', 'label' => 'Temperature', 'units' => [['c' => 'K', 'n' => 'kelvin', 'f' => 1, 'o' => 0], ['c' => '°C', 'n' => 'degree Celsius', 'f' => 1, 'o' => 273.15], ['c' => '°F', 'n' => 'degree Fahrenheit', 'f' => 0.5555555556, 'o' => 255.3722222222]]],
        ]"
        :currencies="[['c' => 'IDR', 'n' => 'Indonesian Rupiah', 'r' => 1], ['c' => 'USD', 'n' => 'US Dollar', 'r' => 15800], ['c' => 'EUR', 'n' => 'Euro', 'r' => null]]" />
</div>
