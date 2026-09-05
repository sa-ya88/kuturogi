import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link } from '@inertiajs/react';

interface NewsArticle {
    id: number;
    title: string;
    content: string;
    published_at: string;
    image?: string | null;
}

export default function NewsShow({ article }: { article: NewsArticle }) {
    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('ja-JP', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
        });
    };

    return (
        <GuestLayout>
            <Head title={article.title} />

            <section className="pt-32 pb-16 bg-[#2d2a26] text-white">
                <div className="max-w-7xl mx-auto px-4 text-center">
                    <h1 className="text-4xl font-light tracking-[0.2em] mb-4">ニュース</h1>
                    <p className="text-stone-400 tracking-widest text-sm md:text-base">最新のお知らせと更新情報</p>
                </div>
            </section>

            <article className="py-20 max-w-3xl mx-auto px-4">
                <Link
                    href={route('news')}
                    className="text-stone-500 hover:text-stone-800 mb-8 inline-block text-sm"
                >
                    ← お知らせ一覧に戻る
                </Link>

                <div className="flex items-center gap-4 mb-6">
                    <span className="inline-block bg-amber-700 text-white text-xs font-bold px-3 py-1 rounded tracking-widest">
                        News
                    </span>
                    <time className="text-sm text-stone-500 font-medium">
                        {formatDate(article.published_at)}
                    </time>
                </div>

                <h2 className="text-2xl md:text-3xl font-bold text-stone-800 mb-8 leading-tight">
                    {article.title}
                </h2>

                {article.image && (
                    <div className="mb-8 overflow-hidden rounded-lg">
                        <img
                            src={article.image}
                            alt={article.title}
                            className="w-full h-auto object-cover"
                        />
                    </div>
                )}

                <p className="text-stone-700 leading-loose whitespace-pre-wrap">
                    {article.content}
                </p>
            </article>
        </GuestLayout>
    );
}
