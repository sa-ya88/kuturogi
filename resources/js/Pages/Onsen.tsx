import GuestLayout from '@/Layouts/GuestLayout';
import { Head } from '@inertiajs/react';

export default function Onsen() {
    return (
        <GuestLayout>
            <Head title="大浴場" />

            <section className="pt-32 pb-16 bg-[#2d2a26] text-white">
                <div className="max-w-7xl mx-auto px-4 text-center">
                    <h1 className="text-4xl font-light tracking-[0.2em] mb-4">大浴場</h1>
                    <p className="text-stone-400 tracking-widest text-sm md:text-base">源泉掛け流し、心解き放つ癒しの湯。</p>
                </div>
            </section>

            <section className="py-20 max-w-5xl mx-auto px-4 space-y-32">

                <div className="flex flex-col md:flex-row-reverse items-center gap-12">
                    <div className="w-full md:w-1/2 overflow-hidden shadow-lg">
                        <img src="/images/onsen1.webp" alt="大浴場" className="hover:scale-105 transition duration-700 h-80 w-full object-cover" />
                    </div>
                    <div className="w-full md:w-1/2">
                        <h2 className="text-2xl mb-6 tracking-widest">檜の香る大浴場</h2>
                        <p className="text-stone-600 leading-loose">
                            香り高い檜を使用した広々とした内湯。<br />
                            源泉のぬくもりが、旅の疲れを芯から癒してくれます。
                        </p>
                    </div>
                </div>

                <div className="flex flex-col md:flex-row items-center gap-12">
                    <div className="w-full md:w-1/2 overflow-hidden shadow-lg">
                        <img src="/images/onsen2.webp" alt="露天風呂" className="hover:scale-105 transition duration-700 h-80 w-full object-cover" />
                    </div>
                    <div className="w-full md:w-1/2">
                        <h2 className="text-2xl mb-6 tracking-widest">四季を愛でる露天風呂</h2>
                        <p className="text-stone-600 leading-loose">
                            昼は青空と山々の緑を、夜は満天の星空を眺めながら。<br />
                            自然の息吹を肌で感じ、至福のひとときをお過ごしください。
                        </p>
                    </div>
                </div>

                <div className="flex flex-col md:flex-row-reverse items-center gap-12">
                    <div className="w-full md:w-1/2 overflow-hidden shadow-lg">
                        <img src="/images/onsen3.webp" alt="大浴場" className="hover:scale-105 transition duration-700 h-80 w-full object-cover" />
                    </div>
                    <div className="w-full md:w-1/2">
                        <h2 className="text-2xl mb-6 tracking-widest">湯灯りの湯</h2>
                        <p className="text-stone-600 leading-loose">
                            やわらかな灯籠と天井の光に包まれる、静寂の内湯。<br />
                            檜と石のぬくもりが溶け合い、心身をゆっくりと解きほぐします。
                        </p>
                    </div>
                </div>
            </section>
        </GuestLayout>
    );
}
