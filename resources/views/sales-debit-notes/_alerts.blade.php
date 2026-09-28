@if(session('success'))
    <div class="alert alert-success mb-4" role="status">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger mb-4" role="alert">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger mb-4" role="alert"><strong>يرجى مراجعة البيانات التالية:</strong>
        <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
