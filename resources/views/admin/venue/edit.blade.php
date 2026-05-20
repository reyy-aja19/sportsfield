@extends('layouts.admin')

@section('content')

<form class="form-card wide-form"
      action="{{ route('admin.venue.update', $venue->id) }}"
      method="POST"
      enctype="multipart/form-data">

    @csrf
    @method('PUT')

    <div class="grid-2">

        <div class="form-group">
            <label>Nama Venue</label>

            <input type="text"
                   name="name"
                   class="input-ui"
                   value="{{ old('name', $venue->name) }}">
        </div>

        <div class="form-group">
            <label>Kota</label>

            <input type="text"
                   name="city"
                   class="input-ui"
                   value="{{ old('city', $venue->city) }}">
        </div>

        <div class="form-group">
            <label>No HP</label>

            <input type="text"
                   name="phone"
                   class="input-ui"
                   value="{{ old('phone', $venue->phone) }}">
        </div>

        <div class="form-group">
            <label>Email</label>

            <input type="email"
                   name="email"
                   class="input-ui"
                   value="{{ old('email', $venue->email) }}">
        </div>

        <div class="form-group">
            <label>Status</label>

            <select name="status" class="select-ui">

                <option value="Aktif"
                    {{ $venue->status == 'Aktif' ? 'selected' : '' }}>
                    Aktif
                </option>

                <option value="Nonaktif"
                    {{ $venue->status == 'Nonaktif' ? 'selected' : '' }}>
                    Nonaktif
                </option>

            </select>
        </div>

    </div>

    <div class="form-group">
        <label>Alamat</label>

        <textarea name="address"
                  class="textarea-ui">{{ old('address', $venue->address) }}</textarea>
    </div>

    <div class="form-group">
    <label>Link Google Maps</label>

    <input type="url"
           name="google_maps"
           class="input-ui"
           value="{{ old('google_maps', $venue->google_maps ?? '') }}"
           placeholder="https://maps.google.com/...">
</div>

    <div class="form-group">
    <label>Embed Google Maps</label>

    <textarea name="map_embed"
              class="textarea-ui"
              rows="5"
              placeholder="<iframe ...></iframe>">{{ old('map_embed', $venue->map_embed ?? '') }}</textarea>
</div>

    <div class="form-group">
        <label>Deskripsi</label>

        <textarea name="description"
                  class="textarea-ui">{{ old('description', $venue->description) }}</textarea>
    </div>

    <div style="display:flex; gap:10px; justify-content:flex-end;">

        <a class="btn-ui btn-gray"
           href="{{ route('admin.venue.index') }}">
            Batal
        </a>

    <div class="form-group preview-shell {{ $venue->photo ? 'has-image' : '' }}">
    <label>Foto Venue Saat Ini</label>

    <img id="venue-preview-image"
         class="preview-img"
         src="{{ $venue->photo ? asset($venue->photo) : 'https://placehold.co/1200x675?text=Preview+Venue' }}"
         alt="{{ $venue->name }}">
</div>

<div class="form-group">
    <label>Ganti Foto Venue</label>

    <input type="file"
           name="photo"
           class="input-ui"
           accept="image/*"
           data-preview="venue-preview-image">
</div>

        <button class="btn-ui btn-green" type="submit">
            Update Venue
        </button>

    </div>

</form>

@endsection