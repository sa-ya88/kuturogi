import { stripeTestCard } from '@/data/demoDummy';

export default function StripeTestCardNotice() {
    return (
        <div className="border border-stone-200 bg-stone-50 p-4 text-xs text-stone-600 space-y-1">
            <p className="font-medium text-stone-800">テストカード情報を入力してください（本番課金はされません）</p>
            <p>番号: {stripeTestCard.number}</p>
            <p>有効期限: {stripeTestCard.expiry}</p>
            <p>CVC: {stripeTestCard.cvc}</p>
            <p>国: 日本</p>
        </div>
    );
}
