<section class="bg-background">
    <div class="grid grid-cols-1 pb-12">
        @if(count($portfolioItems) > 0)
            @foreach ($portfolioItems as $item)
                <div class="mx-auto max-w-5xl px-6
                    @if($item->spacing === 'yes')
                        py-12
                    @elseif($item->spacing === 'top')
                        pt-12
                    @elseif($item->spacing === 'bottom')
                        pb-12
                    @endif">

                <!-- Title with proper text wrapping -->
                <h2 class="pb-4 text-xl font-extrabold text-center text-gray-800 lg:text-2xl whitespace-normal break-words max-w-full">
                    {!! $item->title !!}
                </h2>

                @if(($item->display_mode ?? 'before_after') === 'before_after')
                    <div x-data="{ sliderPos: 50, isDragging: false }" class="md:flex justify-center items-center">
                        <div class="relative rounded-md shadow-md md:w-1/2" x-ref="sliderContainer">
                            <!-- Before Image with Responsive Images -->
                            <div
                                class="h-full w-full"
                                style="clip-path: inset(0% 50% 0% 0%); user-select: none"
                                :style="'clip-path: inset(0% calc(100% - ' + sliderPos + '%) 0% 0%);'">
                                @if($item->getFirstMedia('before_image'))
                                    <img
                                        srcset="{{ $item->getFirstMedia('before_image')->getSrcset() }}"
                                        sizes="(max-width: 768px) 100vw, 50vw"
                                        src="{{ $item->getFirstMedia('before_image')->getUrl() }}"
                                        alt="{{ $item->before_alt ?: 'Before' }}"
                                        draggable="false"
                                        class="h-full w-full rounded-md object-cover object-center" />
                                @endif
                            </div>

                            <!-- After Image with Responsive Images -->
                            <div
                                class="absolute left-0 top-0 h-full w-full"
                                style="user-select: none"
                                :style="'clip-path: inset(0% 0% 0% ' + sliderPos + '%);'">
                                @if($item->getFirstMedia('after_image'))
                                    <img
                                        srcset="{{ $item->getFirstMedia('after_image')->getSrcset() }}"
                                        sizes="(max-width: 768px) 100vw, 50vw"
                                        src="{{ $item->getFirstMedia('after_image')->getUrl() }}"
                                        alt="{{ $item->after_alt ?: 'After' }}"
                                        draggable="false"
                                        class="h-full w-full rounded-md object-cover object-center" />
                                @endif
                            </div>
                            <!-- White Vertical Line -->
                            <div
                                x-ref="line"
                                x-on:mousedown="isDragging = true"
                                x-on:mouseup="isDragging = false"
                                x-on:mousemove.window="
                                    if (isDragging) {
                                        const containerRect = $refs.sliderContainer.getBoundingClientRect()
                                        sliderPos = Math.max(
                                            0,
                                            Math.min(
                                                100,
                                                (($event.clientX - containerRect.left) / containerRect.width) * 100,
                                            ),
                                        )
                                    }
                                "
                                x-on:mouseleave.window="isDragging = false"
                                x-on:mouseup.window="isDragging = false"
                                x-on:touchstart="isDragging = true"
                                x-on:touchend="isDragging = false"
                                x-on:touchmove.window="
                                    if (isDragging) {
                                        const containerRect = $refs.sliderContainer.getBoundingClientRect()
                                        sliderPos = Math.max(
                                            0,
                                            Math.min(
                                                100,
                                                (($event.touches[0].clientX - containerRect.left) /
                                                    containerRect.width) *
                                                    100,
                                            ),
                                        )
                                    }
                                "
                                class="z-1 absolute bottom-0 left-1/2 top-0 w-1 cursor-pointer bg-white"
                                :style="'left: ' + sliderPos + '%; user-select: none;'"></div>

                            <!-- Slider Handle with Arrow Icon (Centered) -->
                            <div
                                x-on:mousedown="isDragging = true"
                                x-on:mouseup="isDragging = false"
                                x-on:mousemove.window="
                                    if (isDragging) {
                                        const containerRect = $refs.sliderContainer.getBoundingClientRect()
                                        sliderPos = Math.max(
                                            0,
                                            Math.min(
                                                100,
                                                (($event.clientX - containerRect.left) / containerRect.width) * 100,
                                            ),
                                        )
                                    }
                                "
                                x-on:mouseleave.window="isDragging = false"
                                x-on:mouseup.window="isDragging = false"
                                x-on:touchstart="isDragging = true"
                                x-on:touchend="isDragging = false"
                                x-on:touchmove.window="
                                    if (isDragging) {
                                        const containerRect = $refs.sliderContainer.getBoundingClientRect()
                                        sliderPos = Math.max(
                                            0,
                                            Math.min(
                                                100,
                                                (($event.touches[0].clientX - containerRect.left) /
                                                    containerRect.width) *
                                                    100,
                                            ),
                                        )
                                    }
                                "
                                class="absolute left-1/2 top-1/2 flex h-6 w-6 -translate-x-1/2 -translate-y-1/2 transform cursor-pointer items-center justify-center rounded-full border border-gray-300 bg-white lg:h-8 lg:w-8"
                                :style="'left: ' + sliderPos + '%; z-index: 2; user-select: none;'"
                                x-ref="handle">
                                <!-- SVG Arrow Icon (Centered) -->
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    alt="arrows"
                                    class="h-4 w-4"
                                    height="1em"
                                    viewBox="0 0 512 512">
                                    <path
                                        d="M505 273c9.4-9.4 9.4-24.6 0-33.9l-96-96c-9.4-9.4-24.6-9.4-33.9 0s-9.4 24.6 0 33.9l55 55L81.9 232l55-55c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0L7 239c-9.4 9.4-9.4 24.6 0 33.9l96 96c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-55-55 348.1 0-55 55c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l96-96z" />
                                </svg>
                            </div>
                        </div>
                        <!-- Description with proper wrapping and alignment -->
                        <div class="md:w-1/2 md:pl-6 flex items-center">
                            <div class="w-full">
                                <p class="mt-4 md:mt-0 text-sm md:text-base lg:text-lg text-gray-800 text-left break-words">
                                    {!! $item->description !!}
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Images Gallery Mode -->
                    <div class="md:flex justify-center items-start gap-6">
                        <div class="md:w-1/2">
                            <div class="grid grid-cols-1 gap-4">
                                @foreach($item->getMedia('images') as $image)
                                    <div class="rounded-md shadow-md overflow-hidden">
                                        <img
                                            srcset="{{ $image->getSrcset() }}"
                                            sizes="(max-width: 768px) 100vw, 50vw"
                                            src="{{ $image->getUrl() }}"
                                            alt="{{ $image->getCustomProperty('alt') ?: $item->title }}"
                                            class="w-full h-auto object-cover object-center" />
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <!-- Description with proper wrapping and alignment -->
                        <div class="md:w-1/2 md:pl-6 flex items-start">
                            <div class="w-full">
                                <p class="mt-4 md:mt-0 text-sm md:text-base lg:text-lg text-gray-800 text-left break-words">
                                    {!! $item->description !!}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Add a subtle divider between entries -->
                <div class="my-8 w-1/2 mx-auto border-t border-gray-200"></div>
            </div>
            @endforeach
        @else
            <div class="mx-auto max-w-5xl px-6 py-12 text-center">
                <p class="text-gray-600">No portfolio items found for this type.</p>
            </div>
        @endif
    </div>
</section>
