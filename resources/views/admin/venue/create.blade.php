@extends('layouts.admin')

@section('content')

<form class="form-card wide-form"
      action="{{ route('admin.venue.store') }}"
      method="POST"
      enctype="multipart/form-data">

    @csrf

    <div class="grid-2">

        <div class="form-group">
            <label>Nama Venue</label>

            <input type="text"
                   name="name"
                   class="input-ui"
                   value="{{ old('name') }}"
                   placeholder="Contoh: GOR Badminton">
        </div>

        <div class="form-group">
            <label>Kota</label>

            <input type="text"
                   name="city"
                   class="input-ui"
                   value="{{ old('city') }}"
                   placeholder="Contoh: Cirebon">
        </div>

        <div class="form-group">
            <label>No HP</label>

            <input type="text"
                   name="phone"
                   class="input-ui"
                   value="{{ old('phone') }}">
        </div>

        <div class="form-group">
            <label>Email</label>

            <input type="email"
                   name="email"
                   class="input-ui"
                   value="{{ old('email') }}">
        </div>

        <div class="form-group">
            <label>Status</label>

            <select name="status" class="select-ui">
                <option value="Aktif">Aktif</option>
                <option value="Nonaktif">Nonaktif</option>
            </select>
        </div>

    </div>

    <div class="form-group">
        <label>Alamat</label>

        <textarea name="address"
                  class="textarea-ui"
                  placeholder="Alamat lengkap venue">{{ old('address') }}</textarea>
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
                  class="textarea-ui"
                  placeholder="Deskripsi venue">{{ old('description') }}</textarea>
    </div>

    <div style="display:flex; gap:10px; justify-content:flex-end;">

        <a class="btn-ui btn-gray"
           href="{{ route('admin.venue.index') }}">
            Batal
        </a>
    
    <div class="form-group preview-shell">
    <label>Preview Foto Venue</label>

    <img id="venue-preview-image"
         class="preview-img"
         src="https://placehold.co/1200x675?text=Preview+Venue"
         alt="Preview venue">
</div>

<div class="form-group">
    <label>Foto Venue</label>

    <input type="file"
           name="photo"
           class="input-ui"
           accept="image/*"
           data-preview="venue-preview-image">
</div>

        <button class="btn-ui btn-green" type="submit">
            Simpan Venue
        </button>

    </div>

</form>

@endsection