<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    @foreach($items as $item)
      @if(!$loop->last)
        <li class="breadcrumb-item"><a href="{{ $item['url'] ?? '#' }}">{{ $item['label'] }}</a></li>
      @else
        <li class="breadcrumb-item active" aria-current="page">{{ $item['label'] }}</li>
      @endif
    @endforeach
  </ol>
</nav> 