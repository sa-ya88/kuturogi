import GuestLayout from '@/Layouts/GuestLayout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Elements, PaymentElement, useElements, useStripe } from '@stripe/react-stripe-js';
import { loadStripe, type Stripe } from '@stripe/stripe-js';
import { FormEvent, useEffect, useMemo, useRef, useState } from 'react';
import DemoNotice from '@/Components/DemoNotice';
import StripeTestCardNotice from '@/Components/StripeTestCardNotice';

type ConfirmProps = {
    input: any;
    room: any;
    plan: any;
    quote: any;
    totalPrice: number;
    pricePerPersonPerNight: number;
    nights: number;
    cancelPolicy?: string[];
    stripeKey?: string | null;
    stripeConfigured?: boolean;
};

type ShellProps = ConfirmProps & {
    paymentError?: string | null;
    paymentReady?: boolean;
    clientSecret?: string | null;
    infoMessage?: string | null;
    autoSubmit?: boolean;
    onBeforeSubmit?: () => Promise<string | null>;
};

function flattenErrors(errors: Record<string, unknown>): string[] {
    return Object.values(errors).flatMap((value) => {
        if (Array.isArray(value)) {
            return value.map(String);
        }
        if (value == null || value === '') {
            return [];
        }
        return [String(value)];
    });
}

function buildStorePayload(input: Record<string, any>, paymentIntentId?: string | null) {
    return {
        plan_id: input.plan_id,
        room_id: input.room_id,
        checkin_date: input.check_in_date || input.checkin_date,
        checkout_date: input.check_out_date || input.checkout_date,
        check_in_date: input.check_in_date || input.checkin_date,
        check_out_date: input.check_out_date || input.checkout_date,
        adult_count: Number(input.adult_count ?? 0),
        child_count: Number(input.child_count ?? 0),
        room_count: Number(input.room_count ?? 1),
        last_name: input.last_name,
        first_name: input.first_name,
        last_name_kana: input.last_name_kana,
        first_name_kana: input.first_name_kana,
        tel: input.tel,
        email: input.email,
        zip_code: input.zip_code,
        address: input.address,
        building: input.building ?? '',
        payment_method: input.payment_method,
        payment_intent_id: paymentIntentId || undefined,
        selected_choices: input.selected_choices ?? [],
        selected_option_ids: (input.selected_option_ids ?? []).map((id: any) => Number(id)),
        representatives: input.representatives ?? [],
    };
}

function ConfirmShell({
    input,
    room,
    plan,
    quote,
    totalPrice,
    pricePerPersonPerNight,
    nights,
    cancelPolicy = [],
    paymentError = null,
    paymentReady = true,
    clientSecret = null,
    infoMessage = null,
    autoSubmit = false,
    onBeforeSubmit,
}: ShellProps) {
    const isCredit = input.payment_method === 'credit';
    const page = usePage();
    const { processing } = useForm({});
    const [submitting, setSubmitting] = useState(false);
    const [localError, setLocalError] = useState<string | null>(null);
    const autoStarted = useRef(false);

    const pageErrors = flattenErrors((page.props.errors as Record<string, unknown>) || {});
    const errorMessages = [
        ...pageErrors,
        ...(localError ? [localError] : []),
        ...(paymentError ? [paymentError] : []),
    ].filter((message, index, arr) => arr.indexOf(message) === index);
    const childPercent = quote?.child_percent ?? 70;
    const busy = processing || submitting;

    const submitReservation = async () => {
        setLocalError(null);
        setSubmitting(true);

        try {
            let paymentIntentId: string | null = null;
            if (onBeforeSubmit) {
                paymentIntentId = await onBeforeSubmit();
                if (!paymentIntentId) {
                    setLocalError('クレジットカードの与信が完了していません。');
                    setSubmitting(false);
                    return;
                }
            }

            await new Promise<void>((resolve, reject) => {
                router.post(route('reservations.store'), buildStorePayload(input, paymentIntentId), {
                    preserveScroll: (page) => Object.keys(page.props.errors ?? {}).length > 0,
                    onError: (errors) => {
                        const messages = flattenErrors(errors as Record<string, unknown>);
                        setLocalError(messages[0] || '予約確定に失敗しました。');
                    },
                    onFinish: () => {
                        setSubmitting(false);
                        resolve();
                    },
                });
            });
        } catch (error: any) {
            setLocalError((current) => current || error?.message || '処理に失敗しました。');
            setSubmitting(false);
        }
    };

    useEffect(() => {
        if (!autoSubmit || autoStarted.current || !paymentReady || busy) {
            return;
        }
        autoStarted.current = true;
        void submitReservation();
    }, [autoSubmit, paymentReady]);

    const submit = async (e: FormEvent) => {
        e.preventDefault();
        await submitReservation();
    };

    return (
        <GuestLayout>
            <Head title="予約内容の確認" />
            <section className="pt-32 pb-20 max-w-6xl mx-auto px-4">
                <h1 className="text-2xl font-light tracking-widest text-center mb-8 text-stone-800">ご予約内容の最終確認</h1>
                <DemoNotice />

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-10">
                    <div className="lg:col-span-2">
                        <div className="bg-white p-8 border border-stone-200 shadow-sm space-y-6">
                            {errorMessages.length > 0 && (
                                <div className="border border-red-200 bg-red-50 text-red-700 text-sm p-4 space-y-1" role="alert">
                                    <p className="font-medium">入力内容を確認してください。</p>
                                    <ul className="list-disc list-inside space-y-1">
                                        {errorMessages.map((message, index) => (
                                            <li key={`${message}-${index}`}>{message}</li>
                                        ))}
                                    </ul>
                                </div>
                            )}
                            {infoMessage && (
                                <div className="border border-emerald-200 bg-emerald-50 text-emerald-800 text-sm p-4" role="status">
                                    {infoMessage}
                                </div>
                            )}
                            <div className="border-b pb-4">
                                <label className="text-[10px] text-stone-400 block mb-1">お部屋・プラン</label>
                                <p className="font-bold">{room.name}</p>
                                <p className="text-sm text-stone-600">{plan.name}</p>
                            </div>
                            <div className="grid grid-cols-2 gap-6 border-b pb-4">
                                <div>
                                    <label className="text-[10px] text-stone-400 block mb-1">チェックイン</label>
                                    <p className="font-medium text-amber-700">
                                        {input.check_in_date || input.checkin_date || '未選択（後で入力してください）'}
                                    </p>
                                </div>
                                <div>
                                    <label className="text-[10px] text-stone-400 block mb-1">チェックアウト</label>
                                    <p className="font-medium">{input.check_out_date || input.checkout_date}</p>
                                </div>
                            </div>
                            <div className="border-b pb-4">
                                <label className="text-[10px] text-stone-400 block mb-1">宿泊人数</label>
                                <p className="font-medium">大人 {input.adult_count}名 / 子供 {input.child_count}名（計 {input.room_count}室）</p>
                            </div>

                            <div className="border-b pb-4">
                                <label className="text-[10px] text-stone-400 block mb-1">お客様情報</label>
                                <div className="space-y-2 text-sm">
                                    <p><span className="text-stone-500">お名前：</span> {input.last_name} {input.first_name}</p>
                                    <p><span className="text-stone-500">フリガナ：</span> {input.last_name_kana} {input.first_name_kana}</p>
                                    {input.tel ? (
                                        <p><span className="text-stone-500">電話番号：</span> {input.tel}</p>
                                    ) : null}
                                    <p><span className="text-stone-500">メール：</span> {input.email}</p>
                                    <p><span className="text-stone-500">住所：</span> {input.zip_code} {input.address} {input.building}</p>
                                    {Number(input.room_count) > 1 && Array.isArray(input.representatives) && (
                                        <div className="pt-2 space-y-1">
                                            <p className="text-stone-500">各部屋の代表者：</p>
                                            {input.representatives.map((name: string, index: number) => (
                                                <p key={`rep-${index}`}>{index + 1}室目：{name || '—'}</p>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            </div>

                            <div className="pb-4">
                                <label className="text-[10px] text-stone-400 block mb-1">お支払い方法</label>
                                <p className="font-medium text-amber-700">
                                    {input.payment_method === 'local' ? '現地決済' : 'クレジットカード（オンライン決済・与信）'}
                                </p>
                            </div>

                            {plan?.choice_options?.length > 0 && (
                                <div className="border-b pb-4">
                                    <label className="text-[10px] text-stone-400 block mb-1">プランの選択項目</label>
                                    <div className="space-y-2 text-sm">
                                        {plan.choice_options.map((option: any, index: number) => (
                                            <p key={index}>
                                                <span className="text-stone-500">{option.prompt}：</span>
                                                {input.selected_choices?.[index] ?? '—'}
                                            </p>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {(quote?.selected_options?.length ?? 0) > 0 && (
                                <div className="border-b pb-4">
                                    <label className="text-[10px] text-stone-400 block mb-1">オプション</label>
                                    <div className="space-y-2 text-sm">
                                        {quote.selected_options.map((option: any) => (
                                            <p key={option.id}>
                                                {option.name}：¥{Number(option.price).toLocaleString()}
                                            </p>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {cancelPolicy?.length > 0 && (
                                <div className="border-b pb-4">
                                    <label className="text-[10px] text-stone-400 block mb-1">キャンセルポリシー</label>
                                    <div className="space-y-1 text-sm text-red-600">
                                        {cancelPolicy.map((line: string) => (
                                            <p key={line}>{line}</p>
                                        ))}
                                    </div>
                                </div>
                            )}

                            <form onSubmit={submit} className="pt-6 space-y-6">
                                {isCredit && (
                                    <div className="border border-stone-200 p-4 space-y-3">
                                        <label className="text-[10px] text-stone-400 block">カード情報</label>
                                        <StripeTestCardNotice />
                                        {!paymentReady && !paymentError && (
                                            <p className="text-sm text-stone-500">決済フォームを準備しています…</p>
                                        )}
                                        {clientSecret && (
                                            <PaymentElement
                                                options={{
                                                    layout: 'tabs',
                                                    paymentMethodOrder: ['card'],
                                                }}
                                            />
                                        )}
                                    </div>
                                )}
                                <button
                                    type="submit"
                                    disabled={busy || (isCredit && !paymentReady)}
                                    className="w-full bg-stone-800 text-white py-4 tracking-widest hover:bg-stone-700 transition font-bold disabled:opacity-50"
                                >
                                    {busy ? '処理中...' : isCredit ? 'カード与信して予約を確定する' : 'この内容で予約を確定する'}
                                </button>
                                <button
                                    type="button"
                                    onClick={() => router.post(route('reservations.details'), input, { preserveScroll: false })}
                                    className="w-full text-sm text-stone-400 hover:text-stone-600"
                                >
                                    入力内容を修正する
                                </button>
                            </form>
                        </div>
                    </div>

                    <div className="h-fit">
                        <div className="bg-stone-800 text-white p-6 shadow-xl rounded-lg">
                            <h3 className="text-lg font-bold mb-6 border-b border-stone-600 pb-2 tracking-widest">料金内訳</h3>
                            <div className="space-y-4 text-sm mb-8">
                                <div className="flex justify-between">
                                    <span>大人 (¥{Number(pricePerPersonPerNight).toLocaleString()} × {input.adult_count}名 × {nights}泊)</span>
                                    <span>¥{Number(quote?.nights?.reduce((sum: number, n: any) => sum + (n.adult_amount || 0), 0) ?? 0).toLocaleString()}</span>
                                </div>
                                {input.child_count > 0 && (
                                    <div className="flex justify-between">
                                        <span>子供 {childPercent}%</span>
                                        <span>¥{Number(quote?.nights?.reduce((sum: number, n: any) => sum + (n.child_amount || 0), 0) ?? 0).toLocaleString()}</span>
                                    </div>
                                )}
                                <div className="flex justify-between opacity-60 text-xs">
                                    <span>部屋数</span>
                                    <span>× {input.room_count}室</span>
                                </div>
                                {(quote?.summary_adjustments ?? []).map((label: string) => (
                                    <p key={label} className="text-xs text-amber-200">※ {label}</p>
                                ))}
                                {(quote?.selected_options ?? []).map((option: any) => (
                                    <div key={option.id} className="flex justify-between text-xs">
                                        <span>オプション: {option.name}</span>
                                        <span>¥{Number(option.price).toLocaleString()}</span>
                                    </div>
                                ))}
                                <div className="border-t border-stone-600 pt-4 flex justify-between items-end">
                                    <span className="text-xs">合計金額 (税込)</span>
                                    <span className="text-2xl font-serif text-amber-400">¥{Number(totalPrice).toLocaleString()}</span>
                                </div>
                            </div>
                            <div className="bg-stone-700 p-4 rounded text-xs text-stone-300 space-y-2">
                                <p className="font-bold text-white mb-2">ご注意</p>
                                <p>• 入湯税（150円/人）は現地でお支払いください</p>
                                <p>• キャンセル料が発生する場合があります</p>
                                {isCredit && <p>• カードは予約時に与信のみ行い、チェックイン時に売上確定します</p>}
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </GuestLayout>
    );
}

function CreditConfirm(props: ConfirmProps & {
    clientSecret: string;
    paymentIntentId: string;
    paymentError: string | null;
}) {
    const stripe = useStripe();
    const elements = useElements();

    const onBeforeSubmit = async (): Promise<string | null> => {
        if (!stripe || !elements) {
            throw new Error('決済の準備ができていません。ページを再読み込みしてください。');
        }

        const { error: submitError } = await elements.submit();
        if (submitError) {
            throw new Error(submitError.message || 'カード情報を確認してください。');
        }

        const returnUrl = (() => {
            const named = route('reservations.confirm', {}, false);
            const path = named.startsWith('http')
                ? new URL(named).pathname
                : (named.startsWith('/') ? named : `/${named}`);
            return `${window.location.origin}${path}`;
        })();

        const result = await stripe.confirmPayment({
            elements,
            redirect: 'if_required',
            confirmParams: {
                return_url: returnUrl,
            },
        });

        if (result.error) {
            const detail = result.error.message
                || (result.error as any).decline_code
                || result.error.code
                || 'カード決済に失敗しました。';
            throw new Error(detail);
        }

        const status = result.paymentIntent?.status;
        if (status !== 'requires_capture' && status !== 'succeeded') {
            throw new Error(`カード与信が完了しませんでした（status: ${status || 'unknown'}）。`);
        }

        return result.paymentIntent?.id || props.paymentIntentId;
    };

    return (
        <ConfirmShell
            {...props}
            clientSecret={props.clientSecret}
            paymentReady
            paymentError={props.paymentError}
            onBeforeSubmit={onBeforeSubmit}
        />
    );
}

export default function Confirm(props: ConfirmProps) {
    const isCredit = props.input.payment_method === 'credit';
    const returnedIntentId = typeof props.input.payment_intent === 'string' ? props.input.payment_intent : null;
    const returnedClientSecret = typeof props.input.payment_intent_client_secret === 'string'
        ? props.input.payment_intent_client_secret
        : null;
    const redirectStatus = String(props.input.redirect_status || '');
    const alreadyAuthorized = Boolean(
        returnedIntentId && (redirectStatus === '' || ['succeeded', 'pending'].includes(redirectStatus)),
    );

    const [clientSecret, setClientSecret] = useState<string | null>(null);
    const [paymentIntentId, setPaymentIntentId] = useState<string | null>(null);
    const [paymentError, setPaymentError] = useState<string | null>(null);
    const [stripePromise, setStripePromise] = useState<Promise<Stripe | null> | null>(null);

    useEffect(() => {
        if (!isCredit) {
            return;
        }

        if (!props.stripeConfigured || !props.stripeKey) {
            setPaymentError('クレジットカード決済は現在利用できません。');
            return;
        }

        setStripePromise(loadStripe(props.stripeKey));

        if (alreadyAuthorized && returnedIntentId) {
            setPaymentIntentId(returnedIntentId);
            setClientSecret(returnedClientSecret);
            return;
        }

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const body = {
            plan_id: props.input.plan_id,
            room_id: props.input.room_id,
            checkin_date: props.input.check_in_date || props.input.checkin_date,
            checkout_date: props.input.check_out_date || props.input.checkout_date,
            adult_count: props.input.adult_count,
            child_count: props.input.child_count,
            room_count: props.input.room_count,
            selected_option_ids: props.input.selected_option_ids ?? [],
        };

        fetch(route('reservations.payment-intent'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify(body),
        })
            .then(async (response) => {
                const json = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(json.message || 'PaymentIntent の作成に失敗しました。');
                }
                setClientSecret(json.client_secret);
                setPaymentIntentId(json.payment_intent_id);
            })
            .catch((error: Error) => {
                setPaymentError(error.message);
            });
    }, [isCredit, props.stripeConfigured, props.stripeKey, alreadyAuthorized, returnedIntentId, returnedClientSecret]);

    const options = useMemo(
        () => (clientSecret ? { clientSecret, appearance: { theme: 'stripe' as const } } : undefined),
        [clientSecret],
    );

    if (!isCredit) {
        return <ConfirmShell {...props} />;
    }

    if (alreadyAuthorized && paymentIntentId) {
        return (
            <ConfirmShell
                {...props}
                paymentReady
                autoSubmit
                infoMessage="カード与信が完了しました。予約を確定しています…"
                onBeforeSubmit={async () => paymentIntentId}
            />
        );
    }

    if (clientSecret && paymentIntentId && stripePromise && options) {
        return (
            <Elements stripe={stripePromise} options={options}>
                <CreditConfirm
                    {...props}
                    clientSecret={clientSecret}
                    paymentIntentId={paymentIntentId}
                    paymentError={paymentError}
                />
            </Elements>
        );
    }

    return (
        <ConfirmShell
            {...props}
            paymentReady={false}
            paymentError={paymentError}
        />
    );
}
