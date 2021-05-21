<?php

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BankController;
use App\Http\Controllers\PairController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ParamController;
use App\Http\Controllers\PayoutController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\BalanceController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\IdentityController;
use App\Http\Controllers\PriorityController;
use App\Http\Controllers\RemitterController;
use App\Http\Controllers\RecipientController;
use App\Http\Controllers\OtrosPagosController;
use App\Http\Controllers\PaymentTypeController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\DLocalConfigController;
use App\Http\Controllers\ExchangeRateController;
use App\Http\Controllers\AccountIncomeController;
use App\Http\Controllers\AuthenticationController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Laravel\Fortify\Features;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('countries', [CountryController::class, 'index']);
Route::get('countries/{country}/banks', [BankController::class, 'indexByCountry']);
Route::get('currencies', [CurrencyController::class, 'index']);
Route::get('pairs', [PairController::class, 'index']);
Route::get('priorities', [PriorityController::class, 'index']);
Route::get('params', [ParamController::class, 'index']);
Route::get('exchange-rate/{base}/{quote}', [ExchangeRateController::class, 'getRate']);
Route::post('orders/comments', [CommentController::class, 'store']);
Route::post('orders/{order}/check-payout', [PayoutController::class, 'checkPayoutStatus']);
Route::get('/export-excel', [OrderController::class, 'export']);
Route::post('/bank-transfer', [BalanceController::class, 'createIncome']);


Route::prefix('users')->group(function() {
    Route::post('register', [UserController::class, 'register'])->middleware('guest');
    Route::post('set-account-type', [UserController::class, 'setAccoutnType'])->middleware(['auth:sanctum', 'role:user']);

    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::post('identity', [IdentityController::class, 'store'])->middleware('role:user');
        Route::post('identity/attach-front-image', [IdentityController::class, 'attachFront'])->middleware('role:user');
        Route::post('identity/attach-back-image', [IdentityController::class, 'attachBack'])->middleware('role:user');
        Route::post('address', [AddressController::class, 'store'])->middleware('role:user');
        Route::post('address/attach-image', [AddressController::class, 'attachImage'])->middleware('role:user');
        Route::post('company', [CompanyController::class, 'store'])->middleware('role:user');
        Route::post('company/attach-document', [DocumentController::class, 'attachDocumentToCompany'])->middleware('role:user');
    });
});

Route::prefix('auth')->group(function() {
    Route::post('login', [AuthenticationController::class, 'login'])->middleware('guest');
    Route::post('logout', [AuthenticationController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('me', [AuthenticationController::class, 'me'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function() {
    Route::prefix('recipients')->group(function () {
        Route::get('/', [RecipientController::class, 'index'])->middleware('role:user');
        Route::get('/{recipient}', [RecipientController::class, 'show'])->middleware('role:user|compliance');
        Route::post('/', [RecipientController::class, 'store'])->middleware('role:user');
        Route::put('/{recipient}', [RecipientController::class, 'update'])->middleware('role:user');
        Route::delete('/{recipient}', [RecipientController::class, 'destroy'])->middleware('role:user');
    });

    Route::prefix('orders')
        ->middleware(['role:user'])
        ->group(function() {
            Route::get('/', [OrderController::class, 'index']);
            Route::get('/{order}', [OrderController::class, 'show']);
            Route::post('/', [OrderController::class, 'store']);
            Route::put('/{order}', [OrderController::class, 'update']);
            Route::post('/{order}/pay-with-balance', [OrderController::class, 'payWithBalance']);
            Route::get('/{order}/generate-support', [OrderController::class, 'generateOrderDocument']);
        });

    Route::prefix('remitters')
        ->middleware(['role:agent'])
        ->group(function() {
            Route::get('/', [RemitterController::class, 'index']);
            Route::post('/', [RemitterController::class, 'store']);
            Route::get('/{remitter}', [RemitterController::class, 'show']);
            Route::put('/{remitter}', [RemitterController::class, 'update']);
            Route::delete('/{remitter}', [RemitterController::class, 'destroy']);
        });

    Route::prefix('payment-types')
        ->group(function() {
            Route::get('/', [PaymentTypeController::class, 'index']);
        });

    Route::prefix('payments')
        ->group(function() {
            Route::get('/', [PaymentController::class, 'index'])->middleware(['role:user']);
            Route::post('/', [PaymentController::class, 'store'])->middleware((['role:user']));
            Route::get('/{payment}', [PaymentController::class, 'show'])->middleware(['role:user']);
        });

    Route::prefix('accounts')
        ->group(function() {
            Route::get('/', [AccountController::class, 'index']);
        });
});

Route::prefix('admin')
    ->middleware('auth:sanctum')
    ->group(function() {

        Route::prefix('users')->group(function() {
            Route::get('/', [UserController::class, 'index'])->middleware('role:super_admin');
            Route::get('/all', [UserController::class, 'all'])->middleware('role:admin|compliance');
            Route::post('/register', [UserController::class, 'registerAdminUser'])->middleware('role:super_admin');
            Route::get('/{user}', [UserController::class, 'show'])->middleware('role:super_admin');
            Route::post('/{user}/add-role', [UserController::class, 'addRole'])->middleware('role:super_admin');
            Route::post('/{user}/remove-role', [UserController::class, 'removeRole'])->middleware('role:super_admin');

            Route::post('/{user}/set-account-type', [UserController::class, 'setAccountTypeToCorporation'])->middleware('role:admin');
            Route::post('/{user}/set-agent', [UserController::class, 'setAgent'])->middleware('role:admin');
            Route::post('/{user}/remove-agent', [UserController::class, 'removeAgent'])->middleware('role:admin');

            Route::post('/{user}/verify-identity', [IdentityController::class, 'verifyIdentity'])->middleware('role:compliance');
            Route::post('/{user}/reject-identity', [IdentityController::class, 'rejectIdentity'])->middleware('role:compliance');

            Route::post('/{user}/verify-address', [AddressController::class, 'verifyAddress'])->middleware('role:compliance');
            Route::post('/{user}/reject-address', [AddressController::class, 'rejectAddress'])->middleware('role:compliance');
            //                  Para administrar los balances y credito de las cuentas
            // Route::post('/{user}/set-credit-amount', [UserController::class, 'setCreditAmount'])->middleware('role:super_admin');
            // Route::post('/{user}/change-balance', [UserController::class, 'changeBalanceAmount'])->middleware('role:super_admin');
            // Route::post('/{user}/add-balance', [UserController::class, 'addRemoveFromBalance'])->middleware('role:super_admin');
            Route::post('/{user}/verify-company', [CompanyController::class, 'verifyCompany'])->middleware('role:compliance');
            Route::post('/{user}/reject-company', [CompanyController::class, 'rejectCompany'])->middleware('role:compliance');
        });

        Route::prefix('currencies')->group(function() {
            Route::get('/', [CurrencyController::class, 'indexAll'])->middleware(['role:admin']);
            Route::get('/{currency}', [CurrencyController::class, 'show'])->middleware(['role:admin']);
            Route::post('/', [CurrencyController::class, 'store'])->middleware(['role:admin']);
            Route::put('/{currency}', [CurrencyController::class, 'update'])->middleware(['role:admin']);
            Route::delete('/{currency}', [CurrencyController::class, 'destroy'])->middleware(['role:admin']);
        });

        Route::prefix('pairs')->group(function() {
            Route::get('/', [PairController::class, 'indexAll'])->middleware(['role:admin']);
            Route::get('/{pair}', [PairController::class, 'show'])->middleware(['role:admin']);
            Route::post('/', [PairController::class, 'store'])->middleware(['role:admin']);
            Route::put('/{pair}', [PairController::class, 'update'])->middleware(['role:admin']);
            Route::delete('/{pair}', [PairController::class, 'destroy'])->middleware(['role:admin']);
            Route::post('/update-rates', [PairController::class, 'updateRates'])->middleware(['role:admin']);
        });

        Route::prefix('priorities')->group(function() {
            Route::get('/', [PriorityController::class, 'adminIndex'])->middleware(['role:admin']);
            Route::get('/{priority}', [PriorityController::class, 'show'])->middleware(['role:admin']);
            Route::post('/', [PriorityController::class, 'store'])->middleware(['role:admin']);
            Route::put('/{priority}', [PriorityController::class, 'update'])->middleware(['role:admin']);
            Route::delete('/{priority}', [PriorityController::class, 'destroy'])->middleware(['role:admin']);
        });

        Route::prefix('params')->middleware(['role:super_admin'])->group(function() {
            Route::get('/', [ParamController::class, 'index']);
            Route::get('/{param}', [ParamController::class, 'show']);
            Route::post('/', [ParamController::class, 'store']);
            Route::put('/{param}', [ParamController::class, 'update']);
            Route::delete('/{param}', [ParamController::class, 'destroy']);
        });

        Route::prefix('banks')->middleware(['role:admin'])->group(function() {
            Route::get('/', [BankController::class, 'index']);
            Route::get('/{bank}', [BankController::class, 'show']);
            Route::post('/', [BankController::class, 'store']);
            Route::put('/{bank}', [BankController::class, 'update']);
            Route::post('/{bank}/activate', [BankController::class, 'activate']);
            Route::post('/{bank}/deactivate', [BankController::class, 'deactivate']);
        });

        Route::prefix('orders')->group(function() {
            Route::get('/', [OrderController::class, 'indexAdmin'])->middleware(['role:admin|compliance']);
            Route::get('/export-excel', [OrderController::class, 'export'])->middleware(['role:admin|compliance']);
            Route::get('/{order}', [OrderController::class, 'showAdmin'])->middleware(['role:admin|compliance']);
            Route::get('/{order}/comments', [CommentController::class, 'index'])->middleware('role:admin');
            Route::post('/{order}/verify-payment', [OrderController::class, 'verifyPayment'])->middleware(['role:compliance']);
            Route::post('/{order}/verify-order', [OrderController::class, 'verifyOrder'])->middleware(['role:compliance']);
            Route::post('/{order}/reject-payment', [OrderController::class, 'rejectPayment'])->middleware(['role:compliance']);
            // Route::post('/{order}/reject-order', [OrderController::class, 'rejectOrder'])->middleware(['role:compliance']);
            Route::get('/{order}/pay-with-balance', [OrderController::class, 'payWithBalance']);
            Route::post('/{order}/verify-order', [OrderController::class, 'verifyOrder'])->middleware(['role:admin|compliance']);
            Route::post('/{order}/reject-payment', [OrderController::class, 'rejectPayment'])->middleware(['role:admin|compliance']);
            Route::post('/{order}/reject-order', [OrderController::class, 'rejectOrder'])->middleware(['role:admin|compliance']);
        });

        Route::prefix('payment-types')
        ->group(function() {
            Route::get('/', [PaymentTypeController::class, 'adminIndex'])->middleware('role:admin');
            Route::post('/', [PaymentTypeController::class, 'store'])->middleware(['role:admin']);
            Route::get('/{paymentType}', [PaymentTypeController::class, 'show'])->middleware(['role:admin']);
            Route::put('/{paymentType}', [PaymentTypeController::class, 'update'])->middleware(['role:admin']);
            Route::delete('/{paymentType}', [PaymentTypeController::class, 'destroy'])->middleware(['role:admin']);
        });

        Route::prefix('accounts')
        ->group(function() {
            Route::get('/', [AccountController::class, 'adminIndex'])->middleware(['role:admin']);
            Route::post('/', [AccountController::class, 'store'])->middleware(['role:admin']);
            Route::get('/{account}', [AccountController::class, 'show'])->middleware(['role:admin']);
            Route::put('/{account}', [AccountController::class, 'update'])->middleware(['role:admin']);
            route::get('/{account}/incomes', [AccountIncomeController::class, 'index'])->middleware(['role:admin']);
            route::post('/{account}/secret-key', [AccountController::class, 'createSecretKey'])->middleware(['role:admin']);
            route::get('/{account}/secret-key', [AccountController::class, 'getSecretKey'])->middleware(['role:admin']);
            // route::post('/{account}/incomes', [AccountIncomeController::class, 'store'])->middleware(['role:admin']);
        });

        Route::prefix('transactions')
        ->group(function(){
            Route::get('/', [TransactionController::class, 'index'])->middleware(['role:admin']);
            Route::post('/', [TransactionController::class, 'store'])->middleware(['role:admin']);
            Route::get('/{transaction}', [TransactionController::class, 'show'])->middleware(['role:admin']);
            Route::post('/{transaction}/reject', [TransactionController::class, 'reject'])->middleware(['role:admin']);
        });

        Route::prefix('d-local')
        ->group(function() {
            Route::post('/{order}/payout', [PayoutController::class, 'createDLocalPayout'])->middleware(['role:admin']);
            Route::post('/{order}/check-payout', [PayoutController::class, 'checkPayoutStatus'])->middleware(['role:admin']);
            Route::post('/{order}/cancel-payout', [PayoutController::class, 'cancelPayout'])->middleware(['role:admin']);
        });

        Route::prefix('d-local-service-config')
        ->middleware('role:admin')
        ->group(function() {
            Route::get('/', [DLocalConfigController::class, 'index']);
            Route::post('/', [DLocalConfigController::class, 'store']);
            Route::get('/{dLocalConfig}', [DLocalConfigController::class, 'show']);
            Route::put('/{dLocalConfig}', [DLocalConfigController::class, 'update']);
            Route::delete('/{dLocalConfig}', [DLocalConfigController::class, 'destroy']);
            Route::post('/{dLocalConfig}/set-token', [DLocalConfigController::class, 'setAccessToken']);
        });
});

// https://pre.otrospagos.com/publico/portal/enlace?id=200057&idcli=102003004&tiidc=01
Route::prefix('otrospagos')->group(function() {
    Route::get('condeu01req', [OtrosPagosController::class, 'condeu01req']);
    Route::post('condeu01req', [OtrosPagosController::class, 'condeu01req']);
    Route::get('condeu', [OtrosPagosController::class, 'condeu01req']);
    Route::post('condeu', [OtrosPagosController::class, 'condeu01req']);
    Route::get('notpag01req', [OtrosPagosController::class, 'notpag01req']);
    Route::post('notpag01req', [OtrosPagosController::class, 'notpag01req']);
    Route::get('notpag', [OtrosPagosController::class, 'notpag01req']);
    Route::post('notpag', [OtrosPagosController::class, 'notpag01req']);
    Route::get('revpag01req', [OtrosPagosController::class, 'condeu01req']);
    Route::post('revpag01req', [OtrosPagosController::class, 'condeu01req']);
    Route::get('revpag', [OtrosPagosController::class, 'condeu01req']);
    Route::post('revpag', [OtrosPagosController::class, 'condeu01req']);
});

/**
 * Email verification
 */
Route::prefix('email')->group(function () {
    Route::get('/verify', function () {
        return redirect()->away('https://app.andeanwide.com/verify-email');
    })->middleware('auth:sanctum')->name('verification.notice');

    Route::get('/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return response('', Response::HTTP_NO_CONTENT);
    })->middleware(['auth:sanctum', 'signed'])->name('verification.verify');

    Route::post('/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return response([
            'message' => 'Link de verificación enviado exitosamente.'
        ]);
    })->middleware(['auth:sanctum', 'throttle:6,1'])->name('verification.send');
});


/**
 * PasswordReset
 */
if(Features::resetPasswords()){
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware(['guest'])
        ->name('password.email');

    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware(['guest'])
        ->name('password.update');
}
