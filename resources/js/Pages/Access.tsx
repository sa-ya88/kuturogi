// resources/js/Pages/Access.tsx
import GuestLayout from '@/Layouts/GuestLayout';
import { Head } from '@inertiajs/react';

export default function Access() {
    return (
        <GuestLayout>
            <Head title="アクセス" />
            <section className="pt-32 pb-16 bg-stone-100 text-center">
                <h1 className="text-4xl font-light tracking-widest">アクセス</h1>
            </section>
            <section className="py-20 max-w-4xl mx-auto px-4">
                <div className="bg-stone-200 w-full h-96 mb-12 flex items-center justify-center text-stone-500">
                    [ Google Map 埋め込みエリア ]
                </div>
                <div className="grid md:grid-cols-2 gap-12">
                    <div>
                        <h2 className="border-b border-stone-300 pb-2 mb-4 font-bold">お車でお越しの方</h2>
                        <p className="text-sm leading-loose">〇〇ICより国道1号線を北へ約20分。駐車場は無料で30台分完備しております。</p>
                    </div>
                    <div>
                        <h2 className="border-b border-stone-300 pb-2 mb-4 font-bold">電車でお越しの方</h2>
                        <p className="text-sm leading-loose">JR〇〇駅より送迎バスで10分。※前日までの予約制となります。</p>
                    </div>
                </div>
            </section>
        </GuestLayout>
    );
}
