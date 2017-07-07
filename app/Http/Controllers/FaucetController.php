<?php

namespace App\Http\Controllers;

use App\Helpers\Functions\Faucets;
use App\Http\Requests\CreateFaucetRequest;
use App\Http\Requests\UpdateFaucetRequest;
use App\Models\Faucet;
use App\Models\PaymentProcessor;
use App\Repositories\FaucetRepository;
use Helpers\Functions\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Response;
use Mews\Purifier\Facades\Purifier;
use Prettus\Repository\Criteria\RequestCriteria;

/**
 * Class FaucetController
 *
 * @author  Rob Attfield <emailme@robertattfield.com> <http://www.robertattfield.com>
 * @package App\Http\Controllers
 */
class FaucetController extends AppBaseController
{
    private $faucetRepository;
    private $userFunctions;
    private $faucetFunctions;

    /**
     * FaucetController constructor.
     *
     * @param FaucetRepository $faucetRepo
     * @param Users            $userFunctions
     * @param Faucets          $faucetFunctions
     */
    public function __construct(FaucetRepository $faucetRepo, Users $userFunctions, Faucets $faucetFunctions)
    {
        $this->faucetRepository = $faucetRepo;
        $this->userFunctions = $userFunctions;
        $this->faucetFunctions = $faucetFunctions;
        $this->middleware('auth', ['except' => ['index', 'show']]);
    }

    /**
     * Display a listing of the Faucet.
     *
     * @param  Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $this->faucetRepository->pushCriteria(new RequestCriteria($request));
        $faucets = null;

        if (Auth::guest() || Auth::user()->hasRole('user') && !Auth::user()->isAnAdmin()) {
            $faucets = $this->faucetRepository->all();
        } else {
            $faucets = $this->faucetRepository->withTrashed()->get();
        }

        $paymentProcessors = PaymentProcessor::orderBy('name', 'asc')->pluck('name', 'id');

        return view('faucets.index')
            ->with('faucets', $faucets)
            ->with('paymentProcessors', $paymentProcessors);
    }

    /**
     * Show the form for creating a new Faucet.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $paymentProcessors = PaymentProcessor::orderBy('name', 'asc')->get();
        $faucetPaymentProcessorIds = null;
        $faucet = null;
        Users::userCanAccessArea(
            Auth::user(),
            'faucets.create',
            [
                'paymentProcessors' => $paymentProcessors,
                'faucetPaymentProcessorIds' => $faucetPaymentProcessorIds
            ],
            [
                'paymentProcessors' => $paymentProcessors,
                'faucetPaymentProcessorIds' => $faucetPaymentProcessorIds
            ]
        );
        return view('faucets.create')
            ->with('paymentProcessors', $paymentProcessors)
            ->with('faucetPaymentProcessorIds', $faucetPaymentProcessorIds)
            ->with('faucet', $faucet);
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
        Users::userCanAccessArea(Auth::user(), 'faucets.store', [], []);
        $this->faucetFunctions->createStoreFaucet($request);

        flash('Faucet added successfully.')->success();

        return redirect(route('faucets.index'));
    }

    /**
     * Display the specified Faucet.
     *
     * @param string $slug
     *
     * @return \Illuminate\View\View
     */
    public function show($slug)
    {
        $faucet = $this->faucetRepository->findByField('slug', $slug)->first();

        $adminUser = $this->userFunctions->adminUser();

        $message = null;
        $referralCode = null;

        if (Auth::guest() && !empty($faucet) && $faucet->isDeleted()) { // If the visitor is a guest, faucet exists, and faucet is soft-deleted
            flash('Faucet not found')->error();
            return redirect(route('faucets.index'));
        } elseif (!Auth::guest()  // If the visitor isn't a guest visitor,
            && Auth::user()->hasRole('user')  // If the visitor is an authenticated user with 'user' role
            && !Auth::user()->isAnAdmin()  // If the visitor is an authenticated user, but without 'owner' role,
            && $faucet->isDeleted() // If the faucet has been soft-deleted
        ) {
            flash('Faucet not found')->error();
            return redirect(route('faucets.index'));
        } else {
            if (!empty($faucet)  // If the faucet exists,
                && $faucet->isDeleted()  // If the faucet is soft-deleted,
                && Auth::user()->isAnAdmin() // If the currently authenticated user has 'owner' role,
            ) {
                $message = 'The faucet has been temporarily deleted. You can restore the faucet or permanently delete it.';

                return view('faucets.show')
                    ->with('adminReferralCode', Faucets::getUserFaucetRefCode($adminUser, $faucet))
                    ->with('faucet', $faucet)
                    ->with('message', $message);
            }
            if (!empty($faucet) && !$faucet->isDeleted()) { // If the faucet exists and isn't soft-deleted
                return view('faucets.show')
                    ->with('adminReferralCode', Faucets::getUserFaucetRefCode($adminUser, $faucet))
                    ->with('faucet', $faucet)
                    ->with('message', $message);
            } else {
                flash('Faucet not found')->error();
                return redirect(route('faucets.index'));
            }
        }
    }

    /**
     * Show the form for editing the specified Faucet.
     *
     * @param string $slug
     *
     * @return \Illuminate\View\View
     */
    public function edit($slug)
    {
        Users::userCanAccessArea(Auth::user(), 'faucets.edit', ['slug' => $slug], ['slug' => $slug]);
        $faucet = $this->faucetRepository->findByField('slug', $slug, true)->first();
        $paymentProcessors = PaymentProcessor::orderBy('name', 'asc')->get();

        $paymentProcessorIds = [];

        foreach ($faucet->paymentProcessors->pluck('id')->toArray() as $key => $value) {
            array_push($paymentProcessorIds, $value);
        }

        if (empty($faucet)) {
            flash('Faucet not found')->error();

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
     * @param string              $slug
     * @param UpdateFaucetRequest $request
     *
     * @return Response
     */
    public function update($slug, UpdateFaucetRequest $request)
    {
        Users::userCanAccessArea(Auth::user(), 'faucets.update', ['slug' => $slug], ['slug' => $slug]);
        $faucet = $this->faucetRepository->findByField('slug', $slug, true)->first();

        if (empty($faucet)) {
            flash('Faucet not found')->error();

            return redirect(route('faucets.index'));
        }

        $this->faucetFunctions->updateFaucet($slug, $request);

        flash('The \''. $faucet->name .'\' faucet was updated successfully!')->success();

        return redirect(route('faucets.index'));
    }

    /**
     * Remove the specified Faucet from storage.
     *
     * @param  string $slug
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy($slug)
    {
        Users::userCanAccessArea(
            Auth::user(),
            'faucets.destroy',
            ['slug' => $slug],
            ['slug' => $slug]
        );
        $faucet = $this->faucetRepository->findByField('slug', $slug, true)->first();

        if (empty($faucet)) {
            flash('Faucet not found')->error();

            return redirect(route('faucets.index'));
        }

        $redirectRoute = route('faucets.index');

        // If the faucet is being deleted from a payment processor's faucet list,
        // create appropriate route and redirect to list after delete completes.
        $input = Input::all();
        if (!empty($input['payment_processor'])) {
            $paymentProcessor = PaymentProcessor::where('slug', self::cleanInput($input)['payment_processor'])->first();
        }

        if (!empty($paymentProcessor)) {
            $redirectRoute = route(
                'payment-processors.faucets',
                [
                    'slug' => $paymentProcessor->slug
                ]
            );
        }

        $this->faucetFunctions->destroyFaucet($slug, false);

        flash('The \''. $faucet->name .'\' faucet was archived/deleted successfully.')->success();

        return redirect($redirectRoute);
    }

    /**
     * Permanently remove the specified Faucet from storage.
     *
     * @param  $slug
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroyPermanently($slug)
    {
        Users::userCanAccessArea(Auth::user(), 'faucets.delete-permanently', ['slug' => $slug], ['slug' => $slug]);
        $faucet = $this->faucetRepository->findByField('slug', $slug, true)->first();
        $logFaucet = $this->faucetRepository->findByField('slug', $slug, true)->first();

        if (empty($faucet)) {
            flash('Faucet not found')->error();

            return redirect(route('faucets.index'));
        }

        $faucetName = $faucet->name;

        $redirectRoute = route('faucets.index');

        // If the faucet is being deleted from a payment processor's faucet list,
        // create appropriate route and redirect to list after delete completes.
        $input = Input::all();

        if (!empty($input['payment_processor'])) {
            $paymentProcessor = PaymentProcessor::where('slug', self::cleanInput($input)['payment_processor'])->first();
        }

        if (!empty($paymentProcessor)) {
            $redirectRoute = route(
                'payment-processors.faucets',
                [
                    'slug' => $paymentProcessor->slug
                ]
            );
        }

        $this->faucetFunctions->destroyUserFaucet(Auth::user(), $faucet);
        $faucet->forceDelete();

        flash('The \''. $faucetName .'\' faucet was permanently deleted!')->success();

        return redirect($redirectRoute);
    }

    /**
     * Restore a soft-deleted faucet.
     *
     * @param  $slug
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function restoreDeleted($slug)
    {
        Users::userCanAccessArea(Auth::user(), 'faucets.restore', ['slug' => $slug], ['slug' => $slug]);
        $faucet = $this->faucetRepository->findByField('slug', $slug, true)->first();

        if (empty($faucet)) {
            flash('Faucet not found')->error();

            return redirect(route('faucets.index'));
        }

        $redirectRoute = route('faucets.index');

        $this->faucetFunctions->restoreFaucet($slug);
        $input = Input::all();

        if (!empty($input['payment_processor'])) {
            $paymentProcessor = PaymentProcessor::where('slug', self::cleanInput($input)['payment_processor'])->first();
        }

        if (!empty($paymentProcessor)) {
            $redirectRoute = route(
                'payment-processors.faucets',
                [
                    'slug' => $paymentProcessor->slug
                ]
            );
        }

        flash('The \''. $faucet->name .'\' faucet was successfully restored!')->success();

        return redirect($redirectRoute);
    }

    private static function cleanInput(array $data)
    {
        $data['payment_processor'] = Purifier::clean($data['payment_processor'], 'generalFields');
        return $data;
    }
}
