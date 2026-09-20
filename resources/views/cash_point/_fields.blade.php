{{-- Cash point create/update form fields. Expects an optional `$agent`. --}}
<div class="form-row">
    <div class="field">
        <label>Payment code</label>
        <input type="text" name="code" value="{{ old('code', $agent?->code) }}" placeholder="e.g. DMN-001" required>
    </div>
    <div class="field">
        <label>Business name</label>
        <input type="text" name="name" value="{{ old('name', $agent?->name) }}" placeholder="e.g. Kilimani Money Point" required>
    </div>
</div>
<div class="form-row">
    <div class="field">
        <label>Owner name</label>
        <input type="text" name="owner_name" value="{{ old('owner_name', $agent?->owner_name) }}">
    </div>
    <div class="field">
        <label>Phone</label>
        <input type="text" name="phone" value="{{ old('phone', $agent?->phone) }}" placeholder="07xxxxxxxx" required>
    </div>
</div>
<div class="form-row">
    <div class="field">
        <label>National ID</label>
        <input type="text" name="national_id" value="{{ old('national_id', $agent?->national_id) }}">
    </div>
    <div class="field">
        <label>Level</label>
        <select name="agent_level" required>
            <option value="bronze" @selected(old('agent_level', $agent?->agent_level) === 'bronze')>Bronze</option>
            <option value="silver" @selected(old('agent_level', $agent?->agent_level) === 'silver')>Silver</option>
            <option value="gold" @selected(old('agent_level', $agent?->agent_level) === 'gold')>Gold</option>
            <option value="platinum" @selected(old('agent_level', $agent?->agent_level) === 'platinum')>Platinum</option>
        </select>
    </div>
</div>
<div class="form-row">
    <div class="field">
        <label>Region</label>
        <input type="text" name="region" value="{{ old('region', $agent?->region) }}">
    </div>
    <div class="field">
        <label>District</label>
        <input type="text" name="district" value="{{ old('district', $agent?->district) }}">
    </div>
</div>
<div class="form-row">
    <div class="field">
        <label>Ward</label>
        <input type="text" name="ward" value="{{ old('ward', $agent?->ward) }}">
    </div>
    <div class="field">
        <label>Status</label>
        <select name="status" required>
            <option value="active" @selected(old('status', $agent?->status) === 'active')>Active</option>
            <option value="suspended" @selected(old('status', $agent?->status) === 'suspended')>Suspended</option>
            <option value="inactive" @selected(old('status', $agent?->status) === 'inactive')>Inactive</option>
        </select>
    </div>
</div>
<div class="field">
    <label>Street</label>
    <input type="text" name="street" value="{{ old('street', $agent?->street) }}">
</div>