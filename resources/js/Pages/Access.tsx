import GuestLayout from '@/Layouts/GuestLayout';
import { Head } from '@inertiajs/react';

export default function Access() {
    return (
        <GuestLayout>
            <Head title="アクセス" />

            <section className="pt-32 pb-16 bg-[#2d2a26] text-white">
                <div className="max-w-7xl mx-auto px-4 text-center">
                    <h1 className="text-4xl font-light tracking-[0.2em] mb-4">アクセス</h1>
                    <p className="text-stone-400 tracking-widest text-sm md:text-base">どうぞ道中、移ろう景色を眺めながら、お気をつけてお越しくださいませ。</p>
                </div>
            </section>

            <section className="py-20 max-w-4xl mx-auto px-4">

                <div className="w-full h-96 mb-12 overflow-hidden rounded shadow-sm border border-stone-200">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d36944.07453943351!2d138.03096507307765!3d35.071094753153396!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x601bb6ce8cb0c32f%3A0xfcfa66ebdf6f1655!2z6Z2Z5bKh55yM!5e0!3m2!1sja!2sjp!4v1784728452916!5m2!1sja!2sjp"
                        className="w-full h-full"
                        style={{ border: 0 }}
                        allowFullScreen={true}
                        loading="lazy"
                        referrerPolicy="no-referrer-when-downgrade"
                        title="周辺地図"
                    />
                </div>

                <div className="bg-stone-50 p-8 rounded-lg border border-stone-200 mb-16">
                    <h2 className="text-xl font-light tracking-widest mb-6 text-stone-800 text-center md:text-left">山彦旅館 LUTUROGI</h2>
                    <dl className="grid grid-cols-1 md:grid-cols-[120px_1fr] gap-x-4 gap-y-4 text-sm text-stone-600">
                        <dt className="font-bold md:border-r md:border-stone-300 md:pr-4">所在地</dt>
                        <dd>〒000-0000 静岡県隠れ里郡山奥町字緑渓谷 108-5</dd>

                        <dt className="font-bold md:border-r md:border-stone-300 md:pr-4">電話番号</dt>
                        <dd>050-0000-0000</dd>

                        <dt className="font-bold md:border-r md:border-stone-300 md:pr-4">カーナビ設定</dt>
                        <dd className="text-stone-500">
                            山間部のためナビの種類によってはルートが正しく表示されない場合がございます。<br />
                            その際は「緑渓谷トンネル」を目印にお越しください。</dd>
                    </dl>
                </div>

                <div className="grid md:grid-cols-2 gap-12">
                    <div>
                        <h3 className="border-b border-stone-300 pb-2 mb-4 font-bold text-stone-800">お車でお越しの方</h3>
                        <p className="text-sm text-stone-600 leading-loose">
                            〇〇ICより国道1号線を北へ進み、県道12号線（渓谷道路）に入り約20分。<br />
                            四季折々の景色をお楽しみいただけるルートです。駐車場は無料で30台分完備しております。
                        </p>
                    </div>
                    <div>
                        <h3 className="border-b border-stone-300 pb-2 mb-4 font-bold text-stone-800">電車でお越しの方</h3>
                        <p className="text-sm text-stone-600 leading-loose">
                            JR〇〇駅より、当館専用の無料送迎バスで約10分。<br />
                            運行時間：14:30 / 15:30 / 16:30（※前日までの完全予約制となりますので事前にご連絡ください）。
                        </p>
                    </div>
                </div>
            </section>
        </GuestLayout>
    );
}
