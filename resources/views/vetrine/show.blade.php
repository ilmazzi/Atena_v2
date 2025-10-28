@extends('layouts.vertical', ['title' => 'Gestione Vetrina'])

@section('content')
<div class="container-xxl">
    @livewire('vetrina-detail', ['id' => $vetrinaId])
</div>
@endsection
