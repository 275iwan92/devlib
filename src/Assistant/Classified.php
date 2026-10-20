<?php

namespace Devlib\Assistant;

// Tool opsional yang menyatakan sensitivitasnya. Tool yang tidak mengimplementasikan ini dianggap Quantity (perilaku lama).
interface Classified
{
    public function sensitivity(): Sensitivity;
}
