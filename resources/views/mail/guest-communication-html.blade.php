@extends('mail.layouts.frame')

@section('content')
    <tr>
        <td style="padding:0 48px 36px 48px; font-family:Arial, Helvetica, sans-serif; font-size:15px; line-height:1.7; color:#1e211c;">
            {!! nl2br(e($emailBody)) !!}
        </td>
    </tr>
@endsection