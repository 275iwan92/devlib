<x-dl::card title="Change Password" icon="fa-key">
    <x-dl::field.password name="old_password" label="Old password" required autocomplete="current-password" />
    <x-dl::field.password name="new_password" label="New password" required autocomplete="new-password" hint="Minimum 8 characters." />
    <x-dl::field.password name="new_password_confirmation" label="Confirm new password" required autocomplete="new-password" />
    <x-dl::action-bar><x-dl::button icon="fa-key" label="Update Password" /></x-dl::action-bar>
</x-dl::card>
<x-dl::card title="Account Recovery" description="Used to prove it is you if you forget your password. No email needed." icon="fa-shield-halved">
    <x-dl::field.date name="birth_date" label="Date of birth" required />
    <x-dl::field.text name="mother_name" label="Mother's maiden name" required />
    <x-dl::field.phone name="phone" label="Registered phone" kind="mobile" required />
    <x-dl::field.select name="question_id" label="Security question" placeholder="Choose a question..." :options="['pet' => 'Name of your first pet', 'school' => 'Name of your primary school', 'city' => 'City where you were born']" required />
    <x-dl::field.text name="answer" label="Your answer" required />
    <x-dl::action-bar><x-dl::button icon="fa-floppy-disk" label="Save Recovery Data" /></x-dl::action-bar>
</x-dl::card>
