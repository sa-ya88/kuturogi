import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link } from '@inertiajs/react';

export default function NewsIndex({ news }: any) {

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('ja-JP', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });
    };

    return (
        <GuestLayout>
            <Head title="ニュース" />

            <section className="pt-32 pb-16 bg-[#2d2a26] text-white">
                <div className="max-w-7xl mx-auto px-4 text-center">
                    <h1 className="text-4xl font-light tracking-[0.2em] mb-4">ニュース</h1>
                    <p className="text-stone-400 tracking-widest text-sm md:text-base">最新のお知らせと更新情報</p>
                </div>
            </section>

            <section className="py-20 max-w-3xl mx-auto px-4">
                {news && news.length > 0 ? (
                    <div className="space-y-6">
                        {news.map((article: any) => (
                            <Link
                                key={article.id}
                                href={route('news.show', article.id)}
                                className="block bg-white border border-stone-200 p-6 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-300 cursor-pointer group"
                            >
                                <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center gap-4 mb-3">
                                            <span className="inline-block bg-amber-700 text-white text-xs font-bold px-3 py-1 rounded tracking-widest">
                                                News
                                            </span>
                                            <time className="text-sm text-stone-500 font-medium">
                                                {formatDate(article.published_at)}
                                            </time>
                                        </div>
                                        <h2 className="text-lg font-bold text-stone-800 mb-2 leading-tight group-hover:text-amber-700 transition-colors">
                                            {article.title}
                                        </h2>
                                        <p className="text-sm text-stone-600 line-clamp-3 leading-relaxed whitespace-pre-wrap">
                                            {article.content}
                                        </p>
                                    </div>
                                    {article.image && (
                                        <div className="w-full sm:w-32 h-24 flex-shrink-0 overflow-hidden rounded-lg">
                                            <img
                                                src={article.image}
                                                alt={article.title}
                                                className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                            />
                                        </div>
                                    )}
                                </div>
                            </Link>
                        ))}
                    </div>
                ) : (
                    <div className="bg-stone-100 p-12 rounded-lg text-center">
                        <p className="text-stone-600 text-lg">現在、配信されているニュースはありません。</p>
                    </div>
                )}
            </section>
        </GuestLayout>
    );
}
