// resources/js/Pages/Top.tsx
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link } from '@inertiajs/react';

// 型定義
interface NewsItem {
    id: number;
    title: string;
    published_at: string;
}

export default function Top({ latestNews }: { latestNews: NewsItem[] }) {
    return (
        <GuestLayout>
            <Head title="トップページ" />

            {/* メインビジュアル */}
            <section className="relative h-screen flex items-center justify-center overflow-hidden">
                <div className="absolute inset-0 bg-stone-900/40 z-10" />
                <img 
                    src="https://unsplash.com" 
                    alt="旅館の外観" 
                    className="absolute inset-0 w-full h-full object-cover"
                />
                <div className="relative z-20 text-center text-white">
                    <h1 className="text-5xl md:text-7xl font-light tracking-[0.2em] mb-6">
                        心安らぐ、<br/>至福のひととき。
                    </h1>
                    <Link 
                        href="/rooms" 
                        className="inline-block border border-white px-10 py-3 hover:bg-white hover:text-stone-900 transition-colors tracking-widest"
                    >
                        プラン一覧を見る
                    </Link>
                </div>
            </section>

            {/* コンセプト紹介 */}
            <section className="py-24 px-4 max-w-3xl mx-auto text-center">
                <span className="text-amber-700 tracking-[0.3em] text-sm uppercase block mb-4">Concept</span>
                <h2 className="text-3xl font-light mb-8 tracking-widest">都会の喧騒を離れ、<br/>四季折々の情景に浸る。</h2>
                <p className="leading-loose text-stone-600">
                    創業百年の歴史が紡ぐ、伝統的なおもてなし。<br/>
                    旬の食材を活かした創作料理と、源泉掛け流しの名湯で、<br/>
                    身も心も解き放たれる時間をお過ごしください。
                </p>
            </section>

            {/* お知らせセクション（追加） */}
            <section className="py-20 bg-white">
                <div className="max-w-4xl mx-auto px-4">
                    <h2 className="text-2xl font-light tracking-widest text-center mb-12">お知らせ</h2>
                    <div className="space-y-6">
                        {latestNews.map((item) => (
                            <div key={item.id} className="flex border-b border-stone-100 pb-4 items-center">
                                <span className="text-xs text-stone-500 w-32">{item.published_at}</span>
                                <Link href="/news" className="flex-1 hover:text-amber-800 transition-colors">
                                    {item.title}
                                </Link>
                            </div>
                        ))}
                    </div>
                    <div className="text-center mt-12">
                        <Link href="/news" className="text-sm border-b border-stone-800 pb-1">
                            すべてのお知らせを見る
                        </Link>
                    </div>
                </div>
            </section>
        </GuestLayout>
    );
}
