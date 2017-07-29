@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="row" style="margin:0 0 0 0;">
            <h1>Terms and Conditions</h1>
        </div>
        <div class="row" style="margin: 0 0 0 0;">
            @if(Auth::user() != null)
                @if(Auth::user()->isAnAdmin())

                    {!! Form::button(
                        '<i class="fa fa-2x fa-edit" style="vertical-align: middle; margin-right:0.25em;"></i>Edit Terms &amp; Conditions',
                        [
                            'type' => 'button',
                            'onClick' => "location.href='terms-and-conditions/edit'",
                            'class' => 'btn btn-primary col-lg-2 col-md-2 col-sm-2 col-xs-12',
                            'style' => 'margin:0.25em 0 0 0; color: white; min-width:12em;'
                        ])
                    !!}
                @endif
            @endif
        </div>
    </section>
    <div class="content">
        <div class="clearfix"></div>
        @include('flash::message')
        <div class="clearfix"></div>
        @include('layouts.partials.navigation._breadcrumbs')
        <div class="box box-primary">
            <div class="box-body">
                <div class="row" style="padding: 0 2em 0 2em;">
                    @if(!empty($termsAndConditions))
                        @include('terms_and_conditions.show_fields')
                    @else
                        @if(!empty($errorMessage))
                            <div class="alert alert-info">
                                <p>
                                    <i class="fa fa-info-circle" aria-hidden="true" style="font-size: 2em; margin-right: 0.5em;"></i>
                                    {!! $errorMessage !!}
                                </p>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('google-analytics')
    @include('layouts.partials.tracking._google_analytics')
@endpush