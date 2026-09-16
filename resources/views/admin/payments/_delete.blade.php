@can('payments.create')
    @if ($payment->status === 'pending' && $payment->paid_at === null)
        <form method="POST" action="{{ route('admin.payments.destroy', $payment) }}" class="d-inline" data-confirm="Delete this pending payment? Its unpaid Stripe checkout will be cancelled. The booking will remain.">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger">Delete pending payment</button>
        </form>
    @endif
@endcan
