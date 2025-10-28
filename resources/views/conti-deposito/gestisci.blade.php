@extends('layouts.vertical', ['title' => 'Gestisci Conto Deposito'])

@section('content')
<div class="container-xxl">
    @livewire('gestisci-conto-deposito', ['depositoId' => $depositoId])
</div>
@endsection

