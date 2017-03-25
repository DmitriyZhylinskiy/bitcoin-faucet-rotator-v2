<?php

namespace App\Http\Controllers;

use App\Helpers\Functions;
use App\Http\Requests\CreateFaucetRequest;
use App\Http\Requests\UpdateFaucetRequest;
use App\Models\PaymentProcessor;
use App\Repositories\FaucetRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laracasts\Flash\Flash as LaracastsFlash;
use Laracasts\Flash\Flash;
use Prettus\Repository\Criteria\RequestCriteria;

class FaucetController extends AppBaseController
{
    /** @var  FaucetRepository */
    private $faucetRepository;

    public function __construct(FaucetRepository $faucetRepo)
    {
        $this->faucetRepository = $faucetRepo;
        $this->middleware('auth', ['except' => ['index', 'show']]);
    }

    /**
     * Display a listing of the Faucet.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $this->faucetRepository->pushCriteria(new RequestCriteria($request));
        $faucets = $this->faucetRepository->withTrashed()->get();

        $user = Auth::user();

        $paymentProcessors = PaymentProcessor::orderBy('name', 'asc')->pluck('name', 'id');

        return view('faucets.index')
            ->with('faucets', $faucets)
            ->with('paymentProcessors', $paymentProcessors);
    }

    /**
     * Show the form for creating a new Faucet.
     *
     * @return Response
     */
    public function create()
    {
        $paymentProcessors = PaymentProcessor::orderBy('name', 'asc')->get();
        $faucetPaymentProcessorIds = null;
        Functions::userCanAccessArea(
            Auth::user(),
            'faucets.create',
            null,
            [
                'paymentProcessors' => $paymentProcessors,
                'faucetPaymentProcessorIds' => $faucetPaymentProcessorIds
            ]
        );
        return view('faucets.create')
            ->with('paymentProcessors', $paymentProcessors)
            ->with('faucetPaymentProcessorIds', $faucetPaymentProcessorIds);
    }

    /**
     * Store a newly created Faucet in storage.
     *
     * @param CreateFaucetRequest $request
     *
     * @return Response
     */
    public function store(CreateFaucetRequest $request)
    {
        Functions::userCanAccessArea(Auth::user(), 'faucets.store');
        $input = $request->except('payment_processors', 'slug');

        $faucet = $this->faucetRepository->create($input);

        $paymentProcessors = $request->get('payment_processors');

        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        $faucet->first()->paymentProcessors->detach();

        if(count($paymentProcessors) >= 1){
            foreach ($paymentProcessors as $paymentProcessorId) {
                $faucet->first()->paymentProcessors->attach((int)$paymentProcessorId);
            }
        }
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        LaracastsFlash::success('Faucet saved successfully.');

        return redirect(route('faucets.index'));
    }

    /**
     * Display the specified Faucet.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function show($slug)
    {
        $faucet = $this->faucetRepository->findByField('slug', $slug)->first();

        if (empty($faucet)) {
            LaracastsFlash::error('Faucet not found');

            return redirect(route('faucets.index'));
        }

        return view('faucets.show')
            ->with('faucet', $faucet);
    }

    /**
     * Show the form for editing the specified Faucet.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function edit($slug)
    {
        Functions::userCanAccessArea(Auth::user(), 'faucets.edit', ['slug' => $slug]);
        $faucet = $this->faucetRepository->findByField('slug', $slug)->first();
        $paymentProcessors = PaymentProcessor::orderBy('name', 'asc')->get();

        $paymentProcessorIds = [];

        foreach($faucet->paymentProcessors->pluck('id')->toArray() as $key => $value){
            array_push($paymentProcessorIds, $value);
        }

        if (empty($faucet)) {
            Flash::error('Faucet not found');

            return redirect(route('faucets.index'));
        }

        return view('faucets.edit')
            ->with('faucet', $faucet)
            ->with('faucetPaymentProcessorIds', $paymentProcessorIds)
            ->with('paymentProcessors', $paymentProcessors);

    }

    /**
     * Update the specified Faucet in storage.
     *
     * @param  int              $id
     * @param UpdateFaucetRequest $request
     *
     * @return Response
     */
    public function update($slug, UpdateFaucetRequest $request)
    {
        Functions::userCanAccessArea(Auth::user(), 'faucets.update', ['slug' => $slug]);
        $currentFaucet = $this->faucetRepository->findByField('slug', $slug)->first();

        $faucet = $this->faucetRepository->update($request->all(), $currentFaucet->id);

        $paymentProcessors = $request->get('payment_processors');
        $paymentProcessorIds = $request->get('payment_processors');

        if(count($paymentProcessorIds) == 1){
            $paymentProcessors = PaymentProcessor::where('id', $paymentProcessorIds[0]);
        }
        else if(count($paymentProcessorIds) >= 1){
            $paymentProcessors = PaymentProcessor::whereIn('id', $paymentProcessorIds);
        }

        if (empty($currentFaucet)) {
            LaracastsFlash::error('Faucet not found');

            return redirect(route('faucets.index'));
        }

        $toAddPaymentProcressorIds = [];


        foreach($paymentProcessors->pluck('id')->toArray() as $key => $value){
            array_push($toAddPaymentProcressorIds, (int)$value);
        }

        //Below logic disables foreign key checking before
        //updating many-to-many table (faucet_payment_processor)
        //that ties instances of faucets to instances of payment
        //processors. The retrieved payment processor ids are
        //iterated through, then synced with the current faucet in
        // the many-many table. Then foreign key checking is re-enabled.
        //DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        if(count($toAddPaymentProcressorIds) > 1){
            $currentFaucet->paymentProcessors()->sync($toAddPaymentProcressorIds);
        }
        else if(count($toAddPaymentProcressorIds) == 1){
            $currentFaucet->paymentProcessors()->sync([$toAddPaymentProcressorIds[0]]);
        }
        //DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        LaracastsFlash::success('Faucet updated successfully.');

        return redirect(route('faucets.index'));
    }

    /**
     * Remove the specified Faucet from storage.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function destroy($slug)
    {
        Functions::userCanAccessArea(Auth::user(), 'faucets.destroy', ['slug' => $slug]);
        $faucet = $this->faucetRepository->findByField('slug', $slug)->first();

        if (empty($faucet)) {
            Flash::error('Faucet not found');

            return redirect(route('faucets.index'));
        }

        $this->faucetRepository->deleteWhere(['slug' => $slug]);

        Flash::success('Faucet deleted successfully.');

        return redirect(route('faucets.index'));
    }

    public function destroyPermanently($slug)
    {
        $user = $this->faucetRepository->findByField('slug', $slug)->first();
        Functions::userCanAccessArea(
            Auth::user(),
            'faucets.delete-permanently',
            null,
            [
                'user' => $user,
                'slug' => $slug
            ]
        );

        if (empty($user)) {
            Flash::error('Faucet not found');

            return redirect(route('faucets.index'));
        }

        $this->faucetRepository->deleteWhere(['slug' => $slug], true);

        Flash::success('Faucet was permanently deleted!');

        return redirect(route('faucets.index'));

    }

    public function restoreDeleted($slug){
        $faucet = $this->faucetRepository->findByField('slug', $slug)->first();
        Functions::userCanAccessArea(
            Auth::user(),
            'faucets.restore',
            null,
            [
                'faucet' => $faucet,
                'slug' => $slug
            ]
        );

        if (empty($faucet)) {
            Flash::error('Faucet not found');

            return redirect(route('faucets.index'));
        }

        $this->faucetRepository->restoreDeleted($slug);

        Flash::success('Faucet was successfully restored!');

        return redirect(route('faucets.index'));

    }
}
