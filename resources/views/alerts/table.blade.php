<table class="table table-responsive" id="alerts-table">
    <thead>
        <tr>
            <th>Alert Type</th>
            <th>Title</th>
            <th>Summary</th>
            @if(!empty(Auth::user()) && Auth::user()->isAnAdmin())
            <th>Action</th>
            @endif
        </tr>
    </thead>
    <tbody>
    @foreach($alerts as $alert)
        @php
            $alertType = $alert->alertType()->first();
            $alertClass = $alertType->bootstrap_alert_class;
            $alertIcon = $alert->alertIcon()->first();
        @endphp
        <tr>
            <td><i
                    class="fa fa-2x {!!  $alertIcon->icon_class !!} alert {!! str_replace('.', '', $alertClass) !!} alert-custom-icon"
                    title="{!! str_replace('fa-', '', $alertIcon->icon_class) !!} ({!! $alertType->name !!})"
                ></i>
            </td>
            <td>
                <a href="{!! route('alerts.show', [$alert->slug]) !!}" title="{!! $alert->title !!}">{!! $alert->title !!}</a></td>
            <td>{!! $alert->summary !!}</td>
            @if(!empty(Auth::user()) && Auth::user()->isAnAdmin())
            <td>
                {!! Form::open(['route' => ['alerts.delete-permanently', $alert->slug], 'method' => 'delete']) !!}
                <div class='btn-group'>
                    <a href="{!! route('alerts.edit', [$alert->slug]) !!}" class='btn btn-default btn-xs'><i class="glyphicon glyphicon-edit"></i></a>
                    {!! Form::button('<i class="glyphicon glyphicon-trash"></i>', ['type' => 'submit', 'class' => 'btn btn-danger btn-xs', 'onclick' => "return confirm('Are you sure?')"]) !!}
                </div>
                {!! Form::close() !!}
            </td>
            @endif
        </tr>
    @endforeach
    </tbody>
</table>