@extends('layouts.admin')

@section('content')

<div class="surface">

    <div class="section-actions">

        <h2>Fasilitas</h2>

    </div>
    @if ($errors->any())
    <div style="color:red; margin-bottom:10px;">
        {{ $errors->first() }}
    </div>
    @endif
  <form action="{{ route('facilities.store') }}"
      method="POST"
      style="display:flex; gap:10px; margin-bottom:20px;">

    @csrf

    <input
        type="text"
        name="name"
        class="input-ui"
        placeholder="Tambah fasilitas..."
        required
    >

    <button class="btn-ui btn-green" type="submit">
        Tambah
    </button>

</form>

    <div class="court-tags">

        @foreach($facilities as $facility)

            <span>

                {{ $facility->name }}

                <form action="{{ route('facilities.destroy', $facility) }}"
                      method="POST">

                    @csrf
                    @method('DELETE')

                    <button type="submit"
                            style="border:0;background:none;color:red;cursor:pointer;">

                        ×

                    </button>

                </form>

            </span>

        @endforeach

    </div>

</div>

@endsection