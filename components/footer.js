export const FooterComponent = `
    <footer class="bg-neutral-900 text-neutral-50 pt-12 pb-8 w-full">
        <div class="container mx-auto px-4">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8 text-center lg:text-left items-start">

            <!-- Project Info -->
            <div class="space-y-4">
                <div class="flex items-center justify-center lg:justify-start">
                <img src="/images/icons/book-borrow-monitoring-system.png" alt="BBMS Logo" class="h-8 w-8">
                <span class="ml-2 text-xl font-bold">Borrow Book Monitoring</span>
                </div>

                <p class="text-gray-400">
                Developed by 
                <a class="underline font-semibold cursor-pointer" href="https://khian.netlify.app" target="_blank">
                    Khian Victory D. Calderon
                </a>
                </p>
            </div>

            <!-- Reference (Right Side on lg, bottom on mobile) -->
            <div class="space-y-4 lg:ml-auto">
                <h3 class="text-lg font-semibold">Reference</h3>
                <p class="not-italic text-gray-400 mt-4">
                <a href="https://github.com/khianvictorycalderon/Borrow-Book-Monitoring-System" target="_blank" title="Project GitHub Source Code">
                    Project Source Code
                </a>
                </p>
            </div>

            </div>

            <div class="border-t border-gray-800 pt-6 flex flex-col md:flex-row items-center justify-center md:justify-between text-center md:text-left">
                <p class="text-gray-500 text-sm mb-4 md:mb-0">
                    © 2026 Borrow Book Monitoring System. All rights reserved.
                </p>
            </div>

        </div>
    </footer>
`;
