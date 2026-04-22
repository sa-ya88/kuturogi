import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';


export default function Thanks() {
    return (
        <GuestLayout>
            <section className="pt-40 pb-20 text-center">
                <h1 className="text-3xl font-light mb-6">ご予約ありがとうございました。</h1>
                <p className="mb-10 text-stone-600">確認メールをお送りいたしましたので、ご確認ください。</p>
                <Link href="/" className="border-b border-stone-800">トップページへ戻る</Link>
            </section>
        </GuestLayout>
    );
}
