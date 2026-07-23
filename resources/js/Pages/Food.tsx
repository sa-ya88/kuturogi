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
                    <p className="text-stone-400 tracking-widest text-sm md:text-base">旬の恵みを、一番美味しい瞬間に。</p>
                </div>
            </section>

            {/* コンテンツセクション */}
            <section className="py-20 max-w-7xl mx-auto px-4 md:px-8 space-y-32">
                
                {/* 夕食セクション */}
                <div className="space-y-12">
                    <div className="text-center max-w-2xl mx-auto">
                        <span className="text-xs tracking-[0.3em] text-stone-400 uppercase block mb-3">— Dinner —</span>
                        <h2 className="text-2xl md:text-3xl mb-6 tracking-widest font-light text-stone-800">一期一会の会席料理</h2>
                        <p className="text-stone-600 leading-loose text-sm md:text-base">
                            地元の山海の幸をふんだんに使い、器や盛り付けにもこだわりました。<br className="hidden md:inline" />
                            目でも舌でも楽しめる、料理長渾身の品々をご堪能ください。
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div className="overflow-hidden rounded shadow-sm">
                            <img src="/images/dinner1.webp" alt="会席料理の一例" className="w-full h-full object-cover transition duration-700" />
                        </div>
                        <div className="overflow-hidden rounded shadow-sm">
                            <img src="/images/dinner2-1.webp" alt="旬の厳選食材" className="w-full h-full object-cover transition duration-700" />
                        </div>
                    </div>
                </div>

                {/* 朝食セクション */}
                <div className="space-y-12">
                    <div className="text-center max-w-2xl mx-auto">
                        <span className="text-xs tracking-[0.3em] text-stone-400 uppercase block mb-3">— Breakfast —</span>
                        <h2 className="text-2xl md:text-3xl mb-6 tracking-widest font-light text-stone-800">至福の朝食バイキング</h2>
                        <p className="text-stone-600 leading-loose text-sm md:text-base">
                            地元の山海の幸を贅沢に揃えた、多彩なメニューをお好きなだけ。<br className="hidden md:inline" />
                            一日の始まりを彩る、出来たての美味しさを心ゆくまでご堪能ください。
                        </p>
                    </div>

                    {/* 朝食は1枚のため、中央配置の大きめなレイアウトに変更 */}
                    <div className="max-w-4xl mx-auto">
                        <div className="h-64 md:h-80 overflow-hidden rounded shadow-sm">
                            <img src="/images/morning1.webp" alt="朝食バイキングのイメージ" className="w-full h-full object-cover transition duration-700" />
                        </div>
                    </div>
                </div>

            </section>
        </GuestLayout>
    );
}
