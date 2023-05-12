@extends('layouts.list')

@section('title', 'Informes')
@section('content-title', 'Informes')

@section('navbar')
@parent
@endsection

@section('list-title', 'Material per taller')
@section('list-content')
    @if(count($tallers) > 0)
    <table class="table table-striped">
  <thead>
    <tr>
      <th scope="col">#</th>
      <th scope="col">Taller</th>
      <th scope="col">Material</th>
      <th scope="col">Responsable</th>
      <th scope="col" class="text-center">Editar el taller per afegir responsables o ajudants</th>
    </tr>
  </thead>
  <tbody>
    @forelse($tallers as $key=>$taller)
    <tr>
      <th scope="row">{{++$key}}</th>
      <td>{{$taller->nom}}</td>
      <td>{{$taller->material}}</td>
      <td>{{$taller->getEncarregat?->nom}} {{$taller->getEncarregat?->cognoms}} </td>
      <td class="text-center"><a href="{{route('taller.edit', $taller->id)}}" class="btn btn-dark">Ves-hi</a></td>
    </tr>
    @empty
    @endforelse
  </tbody>
</table>
    @else
    <p class="text-center fw-bold">No hi han tallers.</p>
    @endif
@endsection