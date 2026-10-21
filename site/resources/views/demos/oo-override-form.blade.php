<script>
    // Turunan JS: hanya method yang berbeda yang ditulis; sisanya diwarisi dari Form standar.
    (window.DevlibReady ||= []).push(Devlib => {
        class FormStockAdjustment extends Devlib.classes.Form {
            // Aturan tambahan: Confirm butuh alasan koreksi; aksi panel lain tetap seperti Form standar.
            act(a) {
                if (a === 'confirm' && !String(this.values().reason ?? '').trim()) {
                    return Devlib.toast('Fill in the reason before you confirm.', 'warn');
                }
                return super.act(a);
            }
        }
        Devlib.register(FormStockAdjustment);
    });
</script>
<x-demo.form-stock-adjustment :back="route('lib', 'p5-override').'?object=form'" :new-url="route('lib', 'p5-override').'?object=form'">
    <x-dl::form.row :cols="3">
        <x-dl::field.text name="item" label="Item" value="Art Paper 150gr" required />
        <x-dl::field.number name="counted" label="Counted qty" :value="1210" :max="100000" required />
        <x-dl::field.text name="reason" label="Reason" hint="The JavaScript subclass asks for this before Confirm." />
    </x-dl::form.row>
</x-demo.form-stock-adjustment>
