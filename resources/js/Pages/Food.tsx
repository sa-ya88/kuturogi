// resources/js/Pages/Food.tsx
import GuestLayout from '@/Layouts/GuestLayout';
import { Head } from '@inertiajs/react';

export default function Food() {
    return (
        <GuestLayout>
            <Head title="お料理" />

            <section className="pt-32 pb-16 bg-[#2d2a26] text-white">
                <div className="max-w-7xl mx-auto px-4 text-center">
                    <h1 className="text-4xl font-light tracking-[0.2em] mb-4">お料理</h1>
                    <p className="text-stone-400 tracking-widest">旬の恵みを、一番美味しい瞬間に。</p>
                </div>
            </section>

            <section className="py-20 max-w-7xl mx-auto px-4">
                <div className="text-center mb-20 max-w-2xl mx-auto">
                    <h2 className="text-2xl mb-8 tracking-widest font-light">一期一会の会席料理</h2>
                    <p className="text-stone-600 leading-loose">
                        地元の山海の幸をふんだんに使い、器や盛り付けにもこだわりました。<br />
                        目でも舌でも楽しめる、料理長渾身の品々をご堪能ください。
                    </p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="h-96 overflow-hidden">
                        <img src="https://unsplash.com" alt="料理1" className="w-full h-full object-cover hover:opacity-90 transition" />
                    </div>
                    <div className="h-96 overflow-hidden">
                        <img src="https://unsplash.com" alt="料理2" className="w-full h-full object-cover hover:opacity-90 transition" />
                    </div>
                </div>
            </section>
        </GuestLayout>
    );
}
