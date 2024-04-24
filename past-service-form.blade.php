<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;
use App\Models\Vendor;
use App\Models\Vehicle;
use App\Models\Service;
use App\Models\Document;

new class extends Component {
    public $form = [
        'vin' => '',
        'vehicle_id' => '',
        'service_name' => '',
        'vendor_name' => '',
        'vendor_id' => '',
        'description' => '',
        'mileage_completed' => '',
        'date_completed' => '',
        'total_cost' => '',

];
    public $vendors;
    public $vehicles;
    public $file;
    public $document;

    public function mount() {
        $this->vehicles = Vehicle::where('company_id', auth()->user()->company_id)->get();
        $this->vendors = Vendor::where('company_id', auth()->user()->company_id)->get();
    }

    #[On('new-auto-form')]
    public function populateForm(array $data): void {
        if (!$data) {
            $this->dispatch('auto-form-success');
            return;
        }
        $form = $data['form'];
        if ($this->document) {
            $this->document->name = $data['document_type'];
        }

        if ($data['document_type'] == 'invoice') {
            $vendorId = $this->checkVendors($form['vendor_name']);
            if ($vendorId) {
                $form['vendor_id'] = $vendorId;
            }
        }

        if ($form['vin']) {
            $vehicle = $this->vehicles->where('vin', intval($form['vin']))->first();
            if ($vehicle) {
                $form['vehicle_id'] = $vehicle->id;
            }
        }

        if ($form['date_completed']) {
            $form['date_completed'] = date('Y-m-d', strtotime($form['date_completed']));
        }

        if ($form['mileage_completed']) {
            $form['mileage_completed'] = number_format($form['mileage_completed']);
        }

        if ($form['total_cost']) {
            $form['total_cost'] = number_format($form['total_cost'], 2);
        }
        $this->dispatch('auto-form-success');
        $this->form = array_replace($this->form, $form);
    }

    #[On('new-document')]
    public function recieveDocument(?Document $document): void {
        $this->document = $document;
    }

    public function save(): void {
        $this->form['mileage_completed'] = intval(str_replace(',', '', $this->form['mileage_completed']));

        // ensure total cost is a float with two decimal places
        $this->form['total_cost'] = bcmul($this->form['total_cost'], '1', 2);

        $this->validate([
            'form.vehicle_id' => 'required|integer',
            'form.name' => 'required|string',
            'form.description' => 'string',
            'form.mileage_completed' => 'required|integer',
            'form.date_completed' => 'required|date',
            'form.total_cost' => 'required|numeric',
            'form.vendor_id' => 'required|integer'
        ]);

        $service = Service::create([
            'vehicle_id' => $this->form['vehicle_id'],
            'name' => $this->form['name'],
            'description' => $this->form['description'],
            'mileage_completed' => $this->form['mileage_completed'],
            'date_completed' => $this->form['date_completed'],
            'total_cost' => $this->form['total_cost'],
            'vendor_id' => $this->form['vendor_id'],
            'company_id' => auth()->user()->company_id,
            'status' => 'completed',
        ]);

        if ($this->document) {
            $this->document->update([
                'name' => 'invoice',
                'service_id' => $service->id,
            ]);
        }
    }

    protected function checkVendors(string $name): int {
        $nameToIdMap = $this->vendors->mapWithKeys(function ($vendor) {
            return [strtolower($vendor->name) => $vendor->id];
        });
        if ($nameToIdMap[strtolower($name)]) {
            return $nameToIdMap[strtolower($name)];
        } else {
            $vendor = Vendor::create([
                'name' => $name,
                'company_id' => auth()->user()->company_id,
            ]);
            $this->vendors->push($vendor);
            $this->form['vendor_id'] = $vendor->id;
            return $vendor->id;
        }
    }
}; ?>

<div x-data="{showAuto: true}">
    <x-header title="Upload Past Services" progress-indicator>
        <x-slot name="actions">
            <x-button x-show="showAuto" x-on:click="showAuto = false" class="btn btn-primary">Manual Entry</x-button>
            <x-button x-show="!showAuto" x-on:click="showAuto = true" class="btn btn-primary">Auto Fill</x-button>
        </x-slot>
    </x-header>
    <div x-show="showAuto" @auto-form-success.window="showAuto = false">
        <livewire:ai.file-to-form :formTemplate="$form" />
    </div>

    <x-form x-show="!showAuto">
        <x-select :options="$vehicles" label="Vehicle" wire:model="form.vehicle_id" inline />
        <x-select :options="$vendors" label="Vendor" wire:model="form.vendor_id" inline />

        <x-input label="Name" wire:model="form.name" inline />
        <x-input label="Description" wire:model="form.description" inline />
        <x-input type="text" label="Mileage Completed" x-model="$wire.form.mileage_completed" inline 
        x-on:input="$wire.form.mileage_completed = $event.target.value.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, ',')" 
        />
        <x-datetime label="Date Completed" wire:model="form.date_completed" />
        <x-input label="Total Cost" wire:model="form.total_cost" inline money suffix="$" />

        <x-button class="btn btn-primary" wire:click="save" spinner="save">Submit</x-button>
    </x-form>
</div>
