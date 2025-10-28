@extends('layouts.vertical', ['title' => 'Gestione Vetrine'])

@section('content')
<div class="container-xxl">
    @livewire('vetrine-table')
</div>
@endsection
