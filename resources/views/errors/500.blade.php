@extends('layouts.app')

@section('title')
    <title>500 Server Error!</title>
@endsection

@section('content')
    <div class="zero-margin">
        <section class="content-header">
            <div class="row {{ empty(Auth::user()) ? 'guest-page-title' : 'auth-page-title' }}">
                <h1>Error 500 - Internal Server Error!</h1>
            </div>
        </section>
        <div class="content {{ empty(Auth::user()) ? 'content-guest' : '' }}">
            <div class="box box-primary">
                <div class="box-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <h2 style="margin-top: 0;"><img src="{{ asset("/assets/images/misc/http-500-internal-server-error.jpg") }}" alt="HTTP Error - Internal Server Error!" class="img-responsive"></h2>
                        </div>
                        <div class="col-lg-6">

                            @if(!empty(Auth::user()) && Auth::user()->isAnAdmin())
                                @if(!empty($message))
                                    <h3 style="margin-top: 0;">A brief message describing the error is below:</h3>
                                    <ul>
                                        <li><strong>{{ $message }}</strong></li>
                                    </ul>

                                @endif
                            @else
                                <p><strong>Please <a href="mailto:{{ \App\Helpers\Functions\Users::adminUser()->email }}?Subject=RE:%20Error%20500%20issue.">contact the site owner</a> for further information.</strong></p>
                            @endif
                            <p><strong>This error has been logged, and related information will be delivered to admin/site developer.</strong></p>

                            @if(!empty(Sentry::getLastEventID()))
                                <p><strong>Please send this ID with your support request: {{ Sentry::getLastEventID() }}.</strong></p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
