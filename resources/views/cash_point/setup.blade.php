@extends('layouts.app')

@section('title', 'Set up cash point')

@section('content')
    <div class="view-head">
        <div>
            <h2>Set up cash point</h2>
            <p class="sub">The cash point (wakala) is the single business this system manages. It is not created automatically — you configure it here once, when you log in.</p>
        </div>
    </div>

    @if (is_admin())
        <div class="panel" style="max-width:860px;">
            <div class="panel-head">
                <h3>Business details</h3>
                @if ($agent !== null)
                    <span class="link" style="color:var(--terracotta-500);">This existing record is incomplete — fill it in to activate it.</span>
                @endif
            </div>
            <form action="{{ route('cash-point.update') }}" method="POST" data-cashpoint-form>
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <div class="panel-body">
                    @if ($errors->any())
                        <div class="form-errors" style="color:var(--danger);margin-bottom:16px;">
                            @foreach ($errors->all() as $error)
                                <div>• {{ $error }}</div>
                            @endforeach
                        </div>
                    @endif
                    @include('cash_point._fields')
                </div>
                <div class="modal-foot">
                    <button type="submit" class="btn btn-primary">Save cash point</button>
                </div>
            </form>
        </div>
    @else
        <div class="panel" style="max-width:720px;">
            <div class="panel-body">
                <p class="empty-state" style="padding:30px 10px;">
                    The cash point has not been set up yet. Ask an administrator to open Settings → Cash Point and configure it, then try again.
                </p>
            </div>
        </div>
    @endif
@endsection

@section('scripts')
    <script>
        document.querySelectorAll('[data-cashpoint-form]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, { method: 'PUT', done: () => setTimeout(() => location.href = '{{ route("cash-point.index") }}', 600) });
            });
        });
    </script>
@endsection