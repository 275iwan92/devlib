<x-dl::card title="Main profile" icon="fa-user">
    <x-dl::field.text name="full_name" label="Full name" value="Sample User" readonly />
    <x-dl::field.text name="birth" label="Date of birth" value="01-Jan-1990" readonly />
    <x-dl::field.text name="phone" label="Phone / WhatsApp" value="+62 800-0000-0000" readonly />
</x-dl::card>
<x-dl::card title="Address & contacts" icon="fa-location-dot">
    <x-dl::field.text name="address" label="Residential address" value="Sample Street 1, Sample City" readonly />
    <x-dl::field.text name="emergency" label="Emergency contact" value="Sample Contact · +62 800-0000-0001" readonly />
</x-dl::card>
<x-dl::side-screen.foot hint="HR verifies the correction before it takes effect">
    <x-dl::button icon="fa-pen-to-square" label="Request correction" variant="secondary" />
</x-dl::side-screen.foot>
