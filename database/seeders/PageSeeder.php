<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Page;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get admin user
        $admin = User::role('admin')->first();

        if (!$admin) {
            $admin = User::first();
        }

        if (!$admin) {
            $this->command->error('No users found in the database. Please create a user first.');
            return;
        }

        // Truncate old pages before seeding
        Page::truncate();

        $this->command->info('Starting to create pages...');

        // Pages to create
        $pages = [
            [
                'user_id' => $admin->id,
                'title' => 'DMCA & Intellectual Property Policy',
                'slug' => 'dmca',
                'content' => '<div class="max-w-5xl mx-auto py-8 px-4">
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-[#0297FE] to-[#d6386a] text-white px-8 py-10">
                            <h1 class="text-4xl font-bold mb-3">DMCA & Intellectual Property Policy</h1>
                            <p class="text-pink-100 text-lg">Protecting intellectual property rights on Blu Lavelle</p>
                            <p class="text-pink-200 text-sm mt-2">Last updated: ' . now()->format('F d, Y') . '</p>
                        </div>

                        <div class="px-8 py-8">
                            <!-- Main Policy Section -->
                            <div class="mb-10">
                                <div class="flex items-start mb-4">
                                    <div class="flex-shrink-0 w-12 h-12 bg-pink-100 rounded-full flex items-center justify-center mr-4">
                                        <svg class="w-6 h-6 text-[#0297FE]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                        </svg>
                                    </div>
                        <div>
                                        <h2 class="text-2xl font-bold text-gray-800 mb-3">Intellectual Property Complaint Policy</h2>
                                        <p class="text-gray-700 leading-relaxed mb-4">
                                            Blu Lavelle provides users with a platform to sell their own press-on nail designs and products. User contractually agree to all terms prior to use of Blu Lavelle services. Blu Lavelle contractually prohibit users from using its services to sell products that infringe upon third party intellectual property rights (such as copyright, trademark, trade dress, and right of publicity).
                                        </p>
                                        <p class="text-gray-700 leading-relaxed">
                                            It is Blu Lavelle policy to block and remove any content that it believes in good faith to infringe the intellectual property rights of third parties following receipt of a compliant notice; and to terminate service for repeated infringement.
                                        </p>
                            </div>
                        </div>
                    </div>

                            <!-- How to Report Section -->
                            <div class="mb-10 bg-amber-50 border-l-4 border-amber-500 rounded-r-lg p-6">
                                <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                                    <svg class="w-6 h-6 text-amber-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    How to Report Infringement
                                </h3>
                                <p class="text-gray-700 mb-4">
                                    If you believe that your intellectual property rights have been infringed upon by a Blu Lavelle user, please notify Blu Lavelle at <a href="mailto:legal@blulavelle.com" class="text-[#0297FE] hover:text-[#d6386a] font-semibold underline">legal@blulavelle.com</a>
                                </p>
                                <p class="text-gray-800 font-semibold mb-3">You must include within your notification the following information:</p>
                                
                                <div class="space-y-3">
                                    <div class="flex items-start bg-white rounded-lg p-4 shadow-sm">
                                        <span class="flex-shrink-0 w-8 h-8 bg-[#0297FE] text-white rounded-full flex items-center justify-center font-bold mr-3">1</span>
                                        <p class="text-gray-700 pt-1">A physical or electronic signature of a person authorized to act on behalf of the owner of the intellectual property that you allege is being infringed</p>
                        </div>
                        
                                    <div class="flex items-start bg-white rounded-lg p-4 shadow-sm">
                                        <span class="flex-shrink-0 w-8 h-8 bg-[#0297FE] text-white rounded-full flex items-center justify-center font-bold mr-3">2</span>
                                        <p class="text-gray-700 pt-1">The URL to the Blu Lavelle product listing(s) used in connection with the allegedly infringing products</p>
                        </div>
                        
                                    <div class="flex items-start bg-white rounded-lg p-4 shadow-sm">
                                        <span class="flex-shrink-0 w-8 h-8 bg-[#0297FE] text-white rounded-full flex items-center justify-center font-bold mr-3">3</span>
                                        <p class="text-gray-700 pt-1">Identification of the copyright, trademark, or other rights that allegedly have been infringed, including proof of ownership (such as copies of existing trademark or copyright registrations)</p>
                        </div>
                        
                                    <div class="flex items-start bg-white rounded-lg p-4 shadow-sm">
                                        <span class="flex-shrink-0 w-8 h-8 bg-[#0297FE] text-white rounded-full flex items-center justify-center font-bold mr-3">4</span>
                                        <p class="text-gray-700 pt-1">Your full name, address, telephone number(s), and email address(es)</p>
                    </div>

                                    <div class="flex items-start bg-white rounded-lg p-4 shadow-sm">
                                        <span class="flex-shrink-0 w-8 h-8 bg-[#0297FE] text-white rounded-full flex items-center justify-center font-bold mr-3">5</span>
                                        <p class="text-gray-700 pt-1">A statement that you have a good-faith belief that use of the material in the URL submitted is unauthorized by the rights owner, or its licensee, and such use amounts to infringement under federal or state law</p>
                        </div>
                        
                                    <div class="flex items-start bg-white rounded-lg p-4 shadow-sm">
                                        <span class="flex-shrink-0 w-8 h-8 bg-[#0297FE] text-white rounded-full flex items-center justify-center font-bold mr-3">6</span>
                                        <p class="text-gray-700 pt-1">A statement, under penalty of perjury, that the information in the notification is complete and accurate and that you are authorized to act on behalf of the owner of the intellectual property or other right that is allegedly infringed</p>
                                    </div>
                        </div>
                    </div>

                            <!-- Counter-Notice Policy -->
                            <div class="mb-10 bg-pink-50 border-l-4 border-[#0297FE] rounded-r-lg p-6">
                                <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                                    <svg class="w-6 h-6 text-[#0297FE] mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Counter-Notice Policy
                                </h3>
                                <div class="bg-white rounded-lg p-5 mb-4">
                                    <p class="text-gray-700 leading-relaxed mb-3">
                                        If you believe that a claim of intellectual property infringement was filed by mistake or misidentification you may file a counter-notice. 
                                    </p>
                                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-3">
                                        <p class="text-red-800 text-sm">
                                            <strong>⚠️ Warning:</strong> If you materially misrepresent in your counter-notice that your design is not infringing upon the intellectual property, you may be liable for damages to the intellectual property owner (including costs and attorney\'s fees). Therefore, if you are unsure whether or not the material infringes on the intellectual property, please contact an attorney before filing the counter-notice.
                                        </p>
                                    </div>
                                    <p class="text-gray-700 mb-3">
                                        The counter-notice should be submitted to <a href="mailto:legal@blulavelle.com" class="text-[#0297FE] hover:text-[#d6386a] font-semibold underline">legal@blulavelle.com</a> and must include:
                                    </p>
                                </div>

                                <div class="space-y-3">
                                    <div class="flex items-start bg-white rounded-lg p-4 shadow-sm">
                                        <span class="flex-shrink-0 w-8 h-8 bg-[#0297FE] text-white rounded-full flex items-center justify-center font-bold mr-3 text-sm">①</span>
                                        <p class="text-gray-700 pt-1">Your physical or electronic signature</p>
                    </div>

                                    <div class="flex items-start bg-white rounded-lg p-4 shadow-sm">
                                        <span class="flex-shrink-0 w-8 h-8 bg-[#0297FE] text-white rounded-full flex items-center justify-center font-bold mr-3 text-sm">②</span>
                                        <p class="text-gray-700 pt-1">Your full name, address, telephone number(s), and email address(es)</p>
                                    </div>
                                    
                                    <div class="flex items-start bg-white rounded-lg p-4 shadow-sm">
                                        <span class="flex-shrink-0 w-8 h-8 bg-[#0297FE] text-white rounded-full flex items-center justify-center font-bold mr-3 text-sm">③</span>
                                        <p class="text-gray-700 pt-1">Identification of the material and its location before it was removed, either by URL to the Blu Lavelle product listing(s) used in connection with the allegedly infringing products or Blu Lavelle order/listing number</p>
                    </div>

                                    <div class="flex items-start bg-white rounded-lg p-4 shadow-sm">
                                        <span class="flex-shrink-0 w-8 h-8 bg-[#0297FE] text-white rounded-full flex items-center justify-center font-bold mr-3 text-sm">④</span>
                                        <p class="text-gray-700 pt-1">A statement under penalty of perjury that the claim of intellectual property infringement that led to the removal or blockage of access to material was filed by mistake or misidentification</p>
                                    </div>
                                    
                                    <div class="flex items-start bg-white rounded-lg p-4 shadow-sm">
                                        <span class="flex-shrink-0 w-8 h-8 bg-[#0297FE] text-white rounded-full flex items-center justify-center font-bold mr-3 text-sm">⑤</span>
                                        <p class="text-gray-700 pt-1">Your consent to the jurisdiction of a federal court in the district where you live (if you are in the U.S.), or your consent to the jurisdiction of a federal court in the district where your service provider is located (if you are not in the U.S.)</p>
                                    </div>
                                    
                                    <div class="flex items-start bg-white rounded-lg p-4 shadow-sm">
                                        <span class="flex-shrink-0 w-8 h-8 bg-[#0297FE] text-white rounded-full flex items-center justify-center font-bold mr-3 text-sm">⑥</span>
                                        <p class="text-gray-700 pt-1">Your consent to accept service of process from the party who submitted the takedown notice or an agent of that party</p>
                                    </div>
                                </div>

                                <div class="bg-pink-50 border border-pink-200 rounded-lg p-4 mt-4">
                                    <p class="text-pink-900 text-sm leading-relaxed">
                                        <strong>📋 Process:</strong> If you submit a counter-notice, a copy may be sent to the complaining party. Unless the intellectual property owner files an action seeking a court order against you, the removed material may be replaced or access to it restored in <strong>10 to 14 business days</strong> after receipt of the counter-notice.
                                    </p>
                                </div>
                            </div>

                            <!-- Repeat Infringement Policy -->
                            <div class="mb-8 bg-red-50 border-l-4 border-red-500 rounded-r-lg p-6">
                                <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                                    <svg class="w-6 h-6 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    Repeat Intellectual Property Complaint Policy
                                </h3>
                                <div class="bg-white rounded-lg p-5 space-y-3">
                                    <p class="text-gray-700 leading-relaxed">
                                        If Blu Lavelle receives repeated notices that you have posted others\' intellectual property without permission, <strong class="text-red-600">Blu Lavelle may terminate your account</strong>. Blu Lavelle has a system for keeping track of repeat violators of intellectual property rights of others, and determining when to suspend or terminate your account.
                                    </p>
                                    <div class="bg-red-100 border border-red-300 rounded-lg p-4">
                                        <p class="text-red-800 font-semibold">
                                            ⚠️ Blu Lavelle reserves the right to terminate accounts that act against the spirit of the Terms of Service, regardless of how many strikes are involved.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Section -->
                            <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg p-6 border border-gray-200">
                                <div class="text-center">
                                    <h3 class="text-lg font-bold text-gray-800 mb-3">Need Assistance?</h3>
                                    <p class="text-gray-700 mb-4">If you require further assistance, please contact us:</p>
                                    <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                                        <a href="mailto:legal@blulavelle.com" class="inline-flex items-center px-6 py-3 bg-[#0297FE] hover:bg-[#d6386a] text-white font-semibold rounded-lg shadow-md transition duration-200">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                            legal@blulavelle.com
                                        </a>
                                        <a href="mailto:support@blulavelle.com" class="inline-flex items-center px-6 py-3 bg-[#0297FE] hover:bg-[#d6386a] text-white font-semibold rounded-lg shadow-md transition duration-200">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                            </svg>
                                            support@blulavelle.com
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>',
                'excerpt' => 'DMCA policy and intellectual property complaint procedures for Blu Lavelle (press-on nail store)',
                'status' => 'published',
                'published_at' => now(),
                'template' => 'default',
                'show_in_menu' => true,
                'menu_title' => 'DMCA',
                'sort_order' => 1,
                'meta_title' => 'DMCA & Intellectual Property Policy - Blu Lavelle',
                'meta_description' => 'Learn about our DMCA policy, how to report intellectual property infringement, counter-notice procedures, and repeat infringement policy.',
            ],
            [
                'user_id' => $admin->id,
                'title' => 'Terms of Service',
                'slug' => 'terms-of-service',
                'content' => '<div class="max-w-6xl mx-auto py-8 px-4">
                    <div class="bg-white rounded-lg shadow-2xl overflow-hidden">
                        <!-- Hero Header -->
                        <div class="bg-gradient-to-r from-[#0297FE] via-[#f89192] to-[#d6386a] text-white px-8 py-12">
                            <h1 class="text-5xl font-bold mb-4">Terms of Service</h1>
                            <p class="text-pink-100 text-xl mb-2">Please read carefully before using the services offered by Blu Lavelle</p>
                            <p class="text-pink-200 text-sm">Last updated: ' . now()->format('F d, Y') . '</p>
                        </div>

                        <div class="px-8 py-8">
                            <!-- Introduction -->
                            <div class="bg-pink-50 border-l-4 border-[#0297FE] rounded-r-lg p-6 mb-8">
                                <p class="text-gray-800 leading-relaxed mb-3">
                                    Blu Lavelle provides users with an automated Internet-based service to design and sell press-on nails and nail products. By using Blu Lavelle and its services in any capacity, you have agreed to the terms and conditions of the Terms of Service ("Agreement") and agree to use the site and service solely as provided in this Agreement.
                                </p>
                            </div>

                            <!-- User Agreement Warning -->
                            <div class="bg-red-100 border-2 border-red-500 rounded-lg p-6 mb-8">
                                <h2 class="text-2xl font-bold text-red-800 mb-3 flex items-center">
                                    <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    USER AGREEMENT
                                </h2>
                                <p class="text-red-900 font-semibold text-lg">
                                    By violating this User Agreement in any capacity, you are subject to an immediate removal of your listing(s), possible forfeit of profit(s), and potential suspension or termination of your account.
                                </p>
                                <p class="text-gray-800 mt-4 leading-relaxed">
                                    Blu Lavelle provides its website and related services to you ("Seller" or "you") subject to this User Agreement (the "Agreement"), the Intellectual Property Complaint Policy, the Counter-Notice Policy, the Repeat Intellectual Property Complaint Policy, the Refund Policy, and the Privacy Policy.
                                </p>
                            </div>

                            <!-- Key Policies Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                                <!-- Delivery -->
                                <div class="bg-gradient-to-br from-pink-50 to-pink-100 border border-pink-300 rounded-lg p-6">
                                    <div class="flex items-center mb-3">
                                        <div class="w-10 h-10 bg-[#0297FE] rounded-full flex items-center justify-center mr-3">
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                                            </svg>
                                        </div>
                                        <h3 class="text-lg font-bold text-pink-800">Delivery Procedure</h3>
                                    </div>
                                    <p class="text-gray-700 text-sm">Any time quoted for delivery is an estimate only. No delay in shipment or delivery of any products relieves Seller of their obligations under this Agreement.</p>
                                </div>

                                <!-- Design Variance -->
                                <div class="bg-gradient-to-br from-pink-50 to-pink-100 border border-pink-300 rounded-lg p-6">
                                    <div class="flex items-center mb-3">
                                        <div class="w-10 h-10 bg-[#0297FE] rounded-full flex items-center justify-center mr-3">
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                                            </svg>
                                        </div>
                                        <h3 class="text-lg font-bold text-pink-800">Design Variance</h3>
                                    </div>
                                    <p class="text-gray-700 text-sm">Nail products are made from designs uploaded by Seller. Design size, placement, and colors may vary slightly by product. Exact design placement and shades are not guaranteed.</p>
                                </div>

                                <!-- Price & Payment -->
                                <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 border border-yellow-300 rounded-lg p-6">
                                    <div class="flex items-center mb-3">
                                        <div class="w-10 h-10 bg-yellow-500 rounded-full flex items-center justify-center mr-3">
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <h3 class="text-lg font-bold text-yellow-800">Price & Payment</h3>
                                    </div>
                                    <p class="text-gray-700 text-sm">Seller determines the price of products; Blu Lavelle processes customer payments; the base price is cost of goods sold; Blu Lavelle remits Seller any amount in excess ("Seller Profits").</p>
                                </div>
                            </div>

                            <!-- Listing Obligations -->
                            <div class="bg-gradient-to-r from-pink-50 to-pink-100 border-2 border-pink-300 rounded-lg p-8 mb-8">
                                <h2 class="text-2xl font-bold text-pink-900 mb-5 flex items-center">
                                    <svg class="w-8 h-8 mr-3 text-[#0297FE]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    BY CREATING A LISTING ON Blu Lavelle:
                                </h2>
                                
                                <div class="space-y-4">
                                    <div class="flex items-start bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex-shrink-0 w-8 h-8 bg-[#0297FE] text-white rounded-full flex items-center justify-center mr-3 mt-1">✓</div>
                                        <p class="text-gray-800">You agree to accept and abide by Blu Lavelle Terms of Service in their entirety.</p>
                                    </div>
                                    
                                    <div class="flex items-start bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex-shrink-0 w-8 h-8 bg-[#0297FE] text-white rounded-full flex items-center justify-center mr-3 mt-1">✓</div>
                                        <p class="text-gray-800">You agree that you are the owner, or licensee, of all rights associated with any created or uploaded artwork or text, including trademarks and copyrights.</p>
                                    </div>
                                    
                                    <div class="flex items-start bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex-shrink-0 w-8 h-8 bg-[#0297FE] text-white rounded-full flex items-center justify-center mr-3 mt-1">✓</div>
                                        <p class="text-gray-800">You agree that the description and title of the listing do not infringe upon the rights of any third party.</p>
                                    </div>
                                    
                                    <div class="flex items-start bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex-shrink-0 w-8 h-8 bg-[#0297FE] text-white rounded-full flex items-center justify-center mr-3 mt-1">✓</div>
                                        <p class="text-gray-800">You understand and agree that Blu Lavelle reserves the right to remove any content that may be considered to promote hate, violence, racial intolerance, or the financial exploitation of a crime.</p>
                                    </div>
                                    
                                    <div class="flex items-start bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex-shrink-0 w-8 h-8 bg-[#0297FE] text-white rounded-full flex items-center justify-center mr-3 mt-1">✓</div>
                                        <p class="text-gray-800">You agree to defend, indemnify, and hold Blu Lavelle harmless from and against any and all claims, damages, costs, and expenses, including attorneys\' fees.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Intellectual Property Rights -->
                            <div class="bg-gradient-to-br from-pink-50 to-rose-50 border-l-4 border-pink-500 rounded-r-lg p-6 mb-8">
                                <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center">
                                    <svg class="w-7 h-7 mr-3 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                    Intellectual Property Rights and License
                                </h2>
                                <div class="bg-white rounded-lg p-5 space-y-3">
                                    <p class="text-gray-700 leading-relaxed">
                                        By submitting listings to Blu Lavelle, you grant Blu Lavelle a <strong>non-exclusive, worldwide, royalty-free, sublicensable and transferable license</strong> to use, reproduce, distribute, prepare derivative works of and display the content of such listings in connection with Blu Lavelle\'s services.
                                    </p>
                                    <p class="text-gray-700 leading-relaxed">
                                        All intellectual property rights in this website and the Blu Lavelle service are owned by or licensed to Blu Lavelle. You may not use, adapt, reproduce, store, distribute, print, display, perform, publish or create derivative works from any part of this website without Blu Lavelle\'s written permission.
                                    </p>
                                </div>
                            </div>

                            <!-- Mobile Terms -->
                            <div class="bg-gradient-to-br from-pink-50 to-pink-100 border-2 border-[#0297FE] rounded-lg p-6 mb-8">
                                <h2 class="text-2xl font-bold text-pink-900 mb-4 flex items-center">
                                    <svg class="w-7 h-7 mr-3 text-[#0297FE]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    Mobile Terms of Service
                                </h2>
                                <div class="bg-white rounded-lg p-5 space-y-3">
                                    <p class="text-gray-700 leading-relaxed">
                                        By consenting to HM FULFILL\'s SMS/text messaging service, you agree to receive recurring SMS/text messages through your wireless provider to the mobile number you provided, even if your mobile number is registered on any Do Not Call list.
                                    </p>
                                    <div class="bg-pink-100 border border-pink-300 rounded-lg p-4">
                                        <p class="text-pink-900"><strong>Opt-out:</strong> Text <strong>STOP</strong> to <strong>+18555255940</strong> or click the unsubscribe link</p>
                                        <p class="text-pink-900 mt-2"><strong>Support:</strong> Text <strong>HELP</strong> to <strong>+18555255940</strong> or email <a href="mailto:admin@blulavelle.com" class="text-[#0297FE] hover:underline font-semibold">admin@blulavelle.com</a></p>
                                    </div>
                                    <p class="text-gray-600 text-sm">Message frequency varies. Message and data rates may apply. You are responsible for all charges from your wireless provider.</p>
                                </div>
                            </div>

                            <!-- Disclaimer & Liability -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                <!-- Disclaimer -->
                                <div class="bg-orange-50 border-2 border-orange-400 rounded-lg p-6">
                                    <h3 class="text-xl font-bold text-orange-800 mb-3 flex items-center">
                                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                        Disclaimer of Warranties
                                    </h3>
                                    <p class="text-gray-700 text-sm leading-relaxed uppercase">
                                        Your use of the Blu Lavelle service is at your sole risk. The service is provided on an "AS IS" and "AS AVAILABLE" basis. Blu Lavelle expressly disclaims all warranties of any kind.
                                    </p>
                                </div>

                                <!-- Liability -->
                                <div class="bg-red-50 border-2 border-red-400 rounded-lg p-6">
                                    <h3 class="text-xl font-bold text-red-800 mb-3 flex items-center">
                                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                        </svg>
                                        Limitation of Liability
                                    </h3>
                                    <p class="text-gray-700 text-sm leading-relaxed">
                                        Blu Lavelle will not be liable for any indirect, incidental, special, or consequential damages. <strong>Total liability will not exceed the amount paid in the last 6 months, or $100, whichever is greater.</strong>
                                    </p>
                                </div>
                            </div>

                            <!-- Buyer Terms -->
                            <div class="bg-gradient-to-r from-pink-50 to-pink-100 border-l-4 border-[#0297FE] rounded-r-lg p-6 mb-8">
                                <h2 class="text-2xl font-bold text-pink-900 mb-5 flex items-center">
                                    <svg class="w-8 h-8 mr-3 text-[#0297FE]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                    </svg>
                                    Buyer Payments, Returns, Refunds & Cancellation
                                </h2>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <h4 class="font-bold text-pink-800 mb-2 flex items-center">
                                            <span class="w-2 h-2 bg-[#0297FE] rounded-full mr-2"></span>
                                            Payment Methods
                                        </h4>
                                        <p class="text-gray-700 text-sm">Blu Lavelle accepts VISA, MASTER, AMERICAN EXPRESS and PayPal. Buyers charged at time of order placement.</p>
                                    </div>
                                    
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <h4 class="font-bold text-pink-800 mb-2 flex items-center">
                                            <span class="w-2 h-2 bg-[#0297FE] rounded-full mr-2"></span>
                                            Shipping Time
                                        </h4>
                                        <p class="text-gray-700 text-sm">Customers can expect to receive products <strong>14-21 business days</strong> after payment. This is an estimate and may vary.</p>
                                    </div>
                                    
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <h4 class="font-bold text-pink-800 mb-2 flex items-center">
                                            <span class="w-2 h-2 bg-[#0297FE] rounded-full mr-2"></span>
                                            International Shipping
                                        </h4>
                                        <p class="text-gray-700 text-sm">Certain countries do not provide international tracking. Blu Lavelle is not responsible for lost or stolen shipments.</p>
                                    </div>
                                    
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <h4 class="font-bold text-pink-800 mb-2 flex items-center">
                                            <span class="w-2 h-2 bg-[#0297FE] rounded-full mr-2"></span>
                                            Returns & Refunds
                                        </h4>
                                        <p class="text-gray-700 text-sm">Email within <strong>30 days</strong> for domestic orders. <strong>60 days</strong> for international orders shipped outside the US.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- API Policy -->
                            <div class="bg-gradient-to-br from-pink-50 to-pink-100 border-2 border-[#0297FE] rounded-lg p-6 mb-8">
                                <h2 class="text-2xl font-bold text-pink-900 mb-4 flex items-center">
                                    <svg class="w-8 h-8 mr-3 text-[#0297FE]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    API (Shopify, WooCommerce, etc.) Policy
                                </h2>
                                <div class="bg-white rounded-lg p-5 space-y-3">
                                    <p class="text-gray-700 leading-relaxed">
                                        Blu Lavelle integrates all information, tools, and services through 3rd Party Platforms (i.e. Shopify) to benefit the Seller. By accessing or using any part of the Blu Lavelle application, the Seller agrees to be bound by this Agreement.
                                    </p>
                                    <div class="bg-pink-100 border border-pink-300 rounded-lg p-4">
                                        <p class="text-pink-900 text-sm">
                                            <strong>Important:</strong> Blu Lavelle does not handle and is not responsible for any Seller services including payment processing, returns, refunds, or exchanges. Blu Lavelle is not responsible for Seller\'s customer service.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Miscellaneous Provisions -->
                            <div class="bg-gradient-to-r from-slate-100 to-gray-100 border-l-4 border-slate-500 rounded-r-lg p-6 mb-8">
                                <h2 class="text-2xl font-bold text-slate-800 mb-5">Miscellaneous Provisions</h2>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="bg-white rounded-lg p-4 border border-slate-200">
                                        <h4 class="font-bold text-slate-700 mb-2">Governing Law</h4>
                                        <p class="text-gray-600 text-sm">These Terms shall be governed by the laws of <strong>Hong Kong</strong>.</p>
                                    </div>
                                    
                                    <div class="bg-white rounded-lg p-4 border border-slate-200">
                                        <h4 class="font-bold text-slate-700 mb-2">Assignment</h4>
                                        <p class="text-gray-600 text-sm">Seller may not assign rights without written consent. Blu Lavelle may assign at its discretion.</p>
                                    </div>
                                    
                                    <div class="bg-white rounded-lg p-4 border border-slate-200">
                                        <h4 class="font-bold text-slate-700 mb-2">Waiver of Jury Trial</h4>
                                        <p class="text-gray-600 text-sm">Each party waives any right to a trial by jury for legal actions arising from this Agreement.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Security Warning -->
                            <div class="bg-gradient-to-r from-amber-100 to-orange-100 border-2 border-amber-500 rounded-lg p-6 mb-8">
                                <h3 class="text-xl font-bold text-amber-900 mb-3 flex items-center">
                                    <svg class="w-7 h-7 mr-2 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                    Exploiting System Vulnerabilities
                                </h3>
                                <p class="text-gray-800 mb-3">
                                    Blu Lavelle preserves the right to deal with individuals and organizations that intentionally exploit system vulnerabilities. Those who exploit vulnerabilities are obligated to recover the damage caused including:
                                </p>
                                <ul class="list-disc list-inside text-gray-700 space-y-1 ml-4">
                                    <li>Loss of money and other benefits</li>
                                    <li>System interruption</li>
                                    <li>Damage caused by DDoS and other forms of attack</li>
                                </ul>
                            </div>

                            <!-- Contact Footer -->
                            <div class="bg-gradient-to-r from-[#0297FE] to-[#d6386a] rounded-lg p-8 text-center text-white">
                                <h3 class="text-2xl font-bold mb-3">Questions About These Terms?</h3>
                                <p class="mb-5 text-pink-100">If you have any questions about these Terms of Service, please contact us:</p>
                                <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                                    <a href="mailto:admin@blulavelle.com" class="inline-flex items-center px-8 py-3 bg-white text-[#0297FE] font-bold rounded-lg shadow-lg hover:shadow-xl transition duration-200">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        admin@blulavelle.com
                                    </a>
                                    <a href="mailto:legal@blulavelle.com" class="inline-flex items-center px-8 py-3 bg-white text-[#0297FE] font-bold rounded-lg shadow-lg hover:shadow-xl transition duration-200">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        legal@blulavelle.com
                                    </a>
                                </div>
                                <p class="mt-6 text-sm text-pink-100">By using Blu Lavelle, you acknowledge that you have read, understood, and agree to be bound by these Terms of Service.</p>
                            </div>
                        </div>
                    </div>
                </div>',
                'excerpt' => 'Complete Terms of Service for Blu Lavelle - User Agreement, Payment Terms, IP Rights, Mobile Terms, API Policy, and Legal Information',
                'status' => 'published',
                'published_at' => now(),
                'template' => 'default',
                'show_in_menu' => true,
                'menu_title' => 'Terms of Service',
                'sort_order' => 2,
                'meta_title' => 'Terms of Service - Blu Lavelle Legal Agreement',
                'meta_description' => 'Read our complete Terms of Service including User Agreement, Intellectual Property Policy, Payment Terms, Mobile Terms, API Policy, and all legal information for using Blu Lavelle (press-on nail store).',
            ],
            [
                'user_id' => $admin->id,
                'title' => 'Our Intellectual Property Policy',
                'slug' => 'our-intellectual-property-policy',
                'content' => '<div class="max-w-5xl mx-auto py-8 px-4">
                    <div class="bg-white rounded-lg shadow-2xl overflow-hidden">
                        <!-- Hero Header -->
                        <div class="bg-gradient-to-r from-[#0297FE] via-[#f89192] to-[#d6386a] text-white px-8 py-12">
                            <h1 class="text-5xl font-bold mb-4">Our Intellectual Property Policy</h1>
                            <p class="text-pink-100 text-xl mb-2">Protecting copyright and intellectual property rights</p>
                            <p class="text-pink-200 text-sm">Last updated: ' . now()->format('F d, Y') . '</p>
                        </div>

                        <div class="px-8 py-8">
                            <!-- Introduction -->
                            <div class="bg-gradient-to-br from-pink-50 to-pink-100 border-l-4 border-[#0297FE] rounded-r-lg p-6 mb-8">
                                <p class="text-gray-800 leading-relaxed mb-4">
                                    Blu Lavelle has adopted the following general policy towards the infringement of copyright and other intellectual property in accordance with general <strong>United States intellectual property laws</strong> and the <strong>Digital Millennium Copyright Act (DMCA)</strong>.
                                </p>
                                <p class="text-gray-800 leading-relaxed">
                                    Blu Lavelle will respond to notices in the form provided below from jurisdictions other than the United States as well.
                                </p>
                            </div>

                            <!-- Contact Information Box -->
                            <div class="bg-gradient-to-r from-pink-100 to-pink-200 border-2 border-[#0297FE] rounded-lg p-6 mb-8">
                                <h2 class="text-2xl font-bold text-pink-900 mb-4 flex items-center">
                                    <svg class="w-8 h-8 mr-3 text-[#0297FE]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    Contact Blu Lavelle Legal Department
                                </h2>
                                <div class="bg-white rounded-lg p-5">
                                    <p class="text-gray-700 mb-4">
                                        Please contact Blu Lavelle\'s Legal Department for any and all Notice and Counter Notice of claims of copyright or other intellectual property infringement:
                                    </p>
                                    <div class="space-y-3">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 w-10 h-10 bg-[#0297FE] rounded-full flex items-center justify-center mr-3">
                                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-gray-800">Email (Preferred Method)</h4>
                                                <a href="mailto:legal@blulavelle.com" class="text-[#0297FE] hover:text-[#d6386a] font-semibold text-lg underline">legal@blulavelle.com</a>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 w-10 h-10 bg-[#0297FE] rounded-full flex items-center justify-center mr-3">
                                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-gray-800">Mailing Address</h4>
                                                <p class="text-gray-700">
                                                    <strong>Attn: Legal Department</strong><br>
                                                    3rd Floor, 24T3 Thanh Xuan Complex Building<br>
                                                    6 Le Van Thiem Street, Thanh Xuan Trung Ward<br>
                                                    Thanh Xuan District, Hanoi, Vietnam
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-4 p-4 bg-pink-50 border border-pink-200 rounded-lg">
                                        <p class="text-gray-800 text-sm">
                                            <strong>Note:</strong> Blu Lavelle\'s Legal Department is the designated agent to receive notifications of alleged intellectual property infringements on the Website.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Repeat Infringer Warning -->
                            <div class="bg-red-100 border-2 border-red-500 rounded-lg p-6 mb-8">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 w-12 h-12 bg-red-500 rounded-full flex items-center justify-center mr-4">
                                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-bold text-red-800 mb-2">Repeat Infringer Policy</h3>
                                        <p class="text-red-900 font-semibold">
                                            Blu Lavelle will terminate rights of subscribers and account holders in appropriate circumstances if they are determined to be repeat infringers.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- A. Reporting Infringements -->
                            <div class="mb-10">
                                <div class="bg-gradient-to-r from-amber-500 to-orange-500 text-white px-6 py-4 rounded-t-lg">
                                    <h2 class="text-3xl font-bold flex items-center">
                                        <span class="w-12 h-12 bg-white text-amber-600 rounded-full flex items-center justify-center mr-3 text-2xl font-bold">A</span>
                                        Reporting Infringements
                                    </h2>
                                </div>
                                <div class="bg-amber-50 border-2 border-amber-300 border-t-0 rounded-b-lg p-6">
                                    <p class="text-gray-800 leading-relaxed mb-4">
                                        Blu Lavelle respects the intellectual property of others, and asks our users to do the same. If you believe that your work has been copied in a way that constitutes copyright infringement, or your intellectual property rights have been otherwise violated, please provide <a href="mailto:legal@blulavelle.com" class="text-[#0297FE] hover:text-[#d6386a] font-semibold underline">legal@blulavelle.com</a> the following information in writing pursuant to the DMCA:
                                    </p>
                                    
                                    <div class="bg-white rounded-lg p-5 mb-4">
                                        <p class="text-gray-700 font-semibold mb-4">Required Information (see Section 512(c)(3) of the Copyright Act):</p>
                                        
                                        <div class="space-y-4">
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 w-10 h-10 bg-amber-500 text-white rounded-full flex items-center justify-center mr-3 font-bold">a</div>
                                                <div class="flex-1">
                                                    <p class="text-gray-800">An electronic or physical <strong>signature</strong> of the person authorized to act on behalf of the owner of the copyright or other intellectual property interest</p>
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 w-10 h-10 bg-amber-500 text-white rounded-full flex items-center justify-center mr-3 font-bold">b</div>
                                                <div class="flex-1">
                                                    <p class="text-gray-800">A <strong>specific description</strong> of the copyrighted work or other intellectual property that you claim to be infringing (if multiple works have been infringed, please provide a list with specific descriptions)</p>
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 w-10 h-10 bg-amber-500 text-white rounded-full flex items-center justify-center mr-3 font-bold">c</div>
                                                <div class="flex-1">
                                                    <p class="text-gray-800">A <strong>specific description of the location</strong> where the material that you claim to be infringing is located in Blu Lavelle (sufficient to permit Blu Lavelle to locate the material)</p>
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 w-10 h-10 bg-amber-500 text-white rounded-full flex items-center justify-center mr-3 font-bold">d</div>
                                                <div class="flex-1">
                                                    <p class="text-gray-800">Your <strong>address, telephone number and email address</strong></p>
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 w-10 h-10 bg-amber-500 text-white rounded-full flex items-center justify-center mr-3 font-bold">e</div>
                                                <div class="flex-1">
                                                    <p class="text-gray-800">A <strong>statement</strong> that you have a good faith belief that the disputed use is not authorized by the copyright or intellectual property owner, its agent or the law</p>
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 w-10 h-10 bg-amber-500 text-white rounded-full flex items-center justify-center mr-3 font-bold">f</div>
                                                <div class="flex-1">
                                                    <p class="text-gray-800">A <strong>statement made under penalty of perjury</strong> that the information in your Notice is accurate and that you are the copyright or intellectual property owner or authorized to act on their behalf</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Process Info -->
                                    <div class="bg-pink-50 border-l-4 border-[#0297FE] rounded-r-lg p-5 mb-4">
                                        <h4 class="font-bold text-pink-900 mb-2 flex items-center">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            What Happens Next
                                        </h4>
                                        <p class="text-gray-700 text-sm">
                                            Once a proper infringement notification is received by Blu Lavelle\'s Legal Department, Blu Lavelle may remove or disable access to the infringing material. When removing or disabling access, Blu Lavelle will make reasonable attempts to inform the allegedly infringing user and may provide a copy of the notice.
                                        </p>
                                    </div>

                                    <!-- Warning Box -->
                                    <div class="bg-yellow-50 border-2 border-yellow-400 rounded-lg p-4">
                                        <p class="text-yellow-900 text-sm">
                                            <strong>⚠️ Important:</strong> If you fail to comply with all of the aforementioned Notice requirements in writing, your Notice may not be valid and Blu Lavelle may ignore such incomplete or inaccurate notices without liability. Under Section 512(f) of the Copyright Act, any person who knowingly materially misrepresents that material or activity is infringing may be subject to liability.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- B. Responding To Infringements -->
                            <div class="mb-8">
                                <div class="bg-gradient-to-r from-[#0297FE] to-[#d6386a] text-white px-6 py-4 rounded-t-lg">
                                    <h2 class="text-3xl font-bold flex items-center">
                                        <span class="w-12 h-12 bg-white text-[#0297FE] rounded-full flex items-center justify-center mr-3 text-2xl font-bold">B</span>
                                        Responding To Infringements
                                    </h2>
                                </div>
                                <div class="bg-pink-50 border-2 border-pink-300 border-t-0 rounded-b-lg p-6">
                                    <p class="text-gray-800 leading-relaxed mb-4">
                                        If you believe that your work has been removed or disabled by mistake or misidentification, please provide <a href="mailto:legal@blulavelle.com" class="text-[#0297FE] hover:text-[#d6386a] font-semibold underline">legal@blulavelle.com</a> with the following information in writing pursuant to the DMCA:
                                    </p>
                                    
                                    <div class="bg-white rounded-lg p-5 mb-4">
                                        <p class="text-gray-700 font-semibold mb-4">Required Information for Counter Notice (see Section 512(g)(3) of the Copyright Act):</p>
                                        
                                        <div class="space-y-4">
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 w-10 h-10 bg-[#0297FE] text-white rounded-full flex items-center justify-center mr-3 font-bold">a</div>
                                                <div class="flex-1">
                                                    <p class="text-gray-800">A physical or electronic <strong>signature</strong> of the subscriber of Blu Lavelle</p>
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 w-10 h-10 bg-[#0297FE] text-white rounded-full flex items-center justify-center mr-3 font-bold">b</div>
                                                <div class="flex-1">
                                                    <p class="text-gray-800"><strong>Identification</strong> of the material that has been removed or to which access has been disabled and the location at which the material appeared before removal</p>
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 w-10 h-10 bg-[#0297FE] text-white rounded-full flex items-center justify-center mr-3 font-bold">c</div>
                                                <div class="flex-1">
                                                    <p class="text-gray-800">A <strong>statement made under penalty of perjury</strong> that the subscriber has a good faith belief that the material was removed or disabled as a result of mistake or misidentification</p>
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 w-10 h-10 bg-[#0297FE] text-white rounded-full flex items-center justify-center mr-3 font-bold">d</div>
                                                <div class="flex-1">
                                                    <p class="text-gray-800">The subscriber\'s <strong>name, address, telephone number</strong>, and a statement that the subscriber consents to the jurisdiction of the Federal District Court (or appropriate judicial district outside the US) and will accept service of process from the complaining party or their agent</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Counter Notice Process -->
                                    <div class="bg-pink-50 border-l-4 border-[#0297FE] rounded-r-lg p-5">
                                        <h4 class="font-bold text-pink-900 mb-3 flex items-center">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                            </svg>
                                            Counter Notice Process
                                        </h4>
                                        <p class="text-gray-700 text-sm leading-relaxed">
                                            If a Counter Notice is received by Blu Lavelle\'s Legal Department, Blu Lavelle may send a copy to the original complaining party. Unless the copyright or intellectual property owner files an action seeking a court order against you, the removed material may be replaced (or access restored) in approximately <strong class="text-pink-800">10 business days</strong> after receipt of the Counter Notice, at the sole discretion of Blu Lavelle\'s Legal Department.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Thank You -->
                            <div class="bg-gradient-to-r from-[#0297FE] to-[#d6386a] rounded-lg p-8 text-center text-white">
                                <div class="flex justify-center mb-4">
                                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center">
                                        <svg class="w-10 h-10 text-[#0297FE]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <h3 class="text-2xl font-bold mb-3">Thank You</h3>
                                <p class="text-lg text-pink-100 mb-5">Thank you for paying attention to these requirements</p>
                                <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                                    <a href="mailto:legal@blulavelle.com" class="inline-flex items-center px-8 py-3 bg-white text-[#0297FE] font-bold rounded-lg shadow-lg hover:shadow-xl transition duration-200">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        Contact Legal Team
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>',
                'excerpt' => 'Our comprehensive intellectual property policy including DMCA procedures, reporting infringements, and counter-notice process',
                'status' => 'published',
                'published_at' => now(),
                'template' => 'default',
                'show_in_menu' => true,
                'menu_title' => 'IP Policy',
                'sort_order' => 3,
                'meta_title' => 'Intellectual Property Policy - Blu Lavelle DMCA & Copyright Protection',
                'meta_description' => 'Learn about our intellectual property policy, DMCA compliance, how to report copyright infringement, and counter-notice procedures on Blu Lavelle (press-on nail store).',
            ],
            [
                'user_id' => $admin->id,
                'title' => 'Intellectual Property Policy',
                'slug' => 'intellectual-property-policy',
                'content' => '<div class="max-w-5xl mx-auto py-10 px-4">
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-[#0297FE] via-[#f89192] to-[#d6386a] text-white px-8 py-12 text-center">
                            <h1 class="text-5xl font-bold mb-3">Intellectual Property Policy</h1>
                            <p class="text-pink-100 text-lg">Copyright, trademarks, and reporting infringement</p>
                            <p class="text-pink-200 text-sm mt-2">Last updated: ' . now()->format('F d, Y') . '</p>
                        </div>

                        <div class="px-8 py-10 space-y-6">
                            <div class="bg-gradient-to-br from-pink-50 to-pink-100 border-l-4 border-[#0297FE] rounded-r-lg p-6">
                                <p class="text-gray-800 leading-relaxed text-lg">
                                    Trang này được giữ để tương thích với các link cũ. Nội dung “Intellectual Property Policy” hiện được trình bày tại trang:
                                    <a href="/our-intellectual-property-policy" class="text-[#0297FE] font-semibold underline hover:text-[#d6386a]">Our Intellectual Property Policy</a>.
                                </p>
                            </div>

                            <div class="bg-white border border-pink-200 rounded-xl p-6">
                                <h2 class="text-2xl font-bold text-gray-900 mb-3">Report an infringement</h2>
                                <p class="text-gray-700 leading-relaxed mb-4">
                                    Nếu bạn cho rằng quyền sở hữu trí tuệ của bạn bị xâm phạm, vui lòng gửi thông tin chi tiết (URL liên quan, bằng chứng sở hữu, thông tin liên hệ)
                                    về địa chỉ email pháp lý:
                                    <a href="mailto:legal@blulavelle.com" class="text-[#0297FE] font-semibold underline hover:text-[#d6386a]">legal@blulavelle.com</a>.
                                </p>
                                <div class="flex flex-col sm:flex-row gap-3">
                                    <a href="/our-intellectual-property-policy" class="inline-flex items-center justify-center px-6 py-3 bg-[#0297FE] hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-md transition duration-200">
                                        View full policy
                                    </a>
                                    <a href="/dmca" class="inline-flex items-center justify-center px-6 py-3 bg-white border-2 border-[#0297FE] text-[#0297FE] hover:bg-pink-50 font-semibold rounded-lg transition duration-200">
                                        DMCA & IP policy
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>',
                'excerpt' => 'Intellectual property policy landing page (legacy URL) linking to the full IP policy and DMCA procedures.',
                'status' => 'published',
                'published_at' => now(),
                'template' => 'default',
                'show_in_menu' => false,
                'menu_title' => 'IP Policy',
                'sort_order' => 999,
                'meta_title' => 'Intellectual Property Policy - Blu Lavelle',
                'meta_description' => 'Learn about Blu Lavelle intellectual property policy and how to report infringement. Full policy and DMCA procedures available.',
            ],
            [
                'user_id' => $admin->id,
                'title' => 'Returns & Exchanges Policy',
                'slug' => 'returns-exchanges-policy',
                'content' => '<div class="max-w-6xl mx-auto py-8 px-4">
                    <div class="bg-white rounded-lg shadow-2xl overflow-hidden">
                        <!-- Hero Header -->
                        <div class="bg-gradient-to-r from-[#0297FE] via-[#f89192] to-[#d6386a] text-white px-8 py-12">
                            <h1 class="text-5xl font-bold mb-4">Returns & Exchanges Policy – Blu Lavelle</h1>
                            <p class="text-pink-100 text-xl mb-2">Your satisfaction is our priority - Easy returns within 30 days</p>
                            <p class="text-pink-200 text-sm">Last updated: ' . now()->format('F d, Y') . '</p>
                        </div>

                        <div class="px-8 py-8">
                            <!-- Introduction -->
                            <div class="bg-gradient-to-br from-pink-50 to-pink-100 border-l-4 border-[#0297FE] rounded-r-lg p-6 mb-8">
                                <p class="text-gray-800 leading-relaxed text-lg">
                                    At <strong>Blu Lavelle</strong>, we offer returns and exchanges within <strong>30 days</strong> from the date you receive your order. If you need to return or exchange an item, please contact our customer support team to submit your request.
                                </p>
                            </div>

                            <!-- No Restocking Fee -->
                            <div class="bg-gradient-to-r from-pink-100 to-pink-200 border-2 border-[#0297FE] rounded-lg p-6 mb-8">
                                <div class="flex items-center justify-center">
                                    <div class="text-center">
                                        <div class="flex justify-center mb-4">
                                            <div class="w-20 h-20 bg-[#0297FE] rounded-full flex items-center justify-center">
                                                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <h2 class="text-3xl font-bold text-pink-900 mb-2">1. Restocking Fee</h2>
                                        <p class="text-5xl font-bold text-[#0297FE]">NO FEE</p>
                                        <p class="text-gray-700 mt-2">Blu Lavelle does not charge any restocking fees.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Return Criteria Section -->
                            <div class="mb-10">
                                <div class="bg-gradient-to-r from-[#0297FE] to-[#d6386a] text-white px-6 py-4 rounded-t-lg mb-0">
                                    <h2 class="text-3xl font-bold">2. Conditions Eligible for Returns & Exchanges</h2>
                                    <p class="text-pink-100 mt-2">You may request a return or exchange only if your item falls under one of the following categories:</p>
                                </div>

                                <!-- Criteria Cards -->
                                <div class="space-y-6 mt-6">
                                    <!-- Criterion A -->
                                    <div class="bg-gradient-to-br from-red-50 to-rose-50 border-l-4 border-red-500 rounded-r-lg p-6">
                                        <div class="flex items-start mb-4">
                                            <div class="flex-shrink-0 w-14 h-14 bg-red-500 text-white rounded-full flex items-center justify-center mr-4 text-2xl font-bold">A</div>
                                            <div class="flex-1">
                                                <h3 class="text-2xl font-bold text-red-800 mb-3">a. Wrong / Damaged / Defective Products</h3>
                                                <p class="text-gray-700 mb-4">Blu Lavelle will fully support cases where:</p>
                                            </div>
                                        </div>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 ml-0 md:ml-18">
                                            <div class="bg-white rounded-lg p-4 shadow-sm border border-red-200">
                                                <div class="flex items-start">
                                                    <svg class="w-6 h-6 text-red-500 mr-3 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                    <p class="text-gray-800">The product does not match the description on the website: <strong>wrong item, wrong material, wrong size</strong>.</p>
                                                </div>
                                            </div>
                                            
                                            <div class="bg-white rounded-lg p-4 shadow-sm border border-red-200">
                                                <div class="flex items-start">
                                                    <svg class="w-6 h-6 text-red-500 mr-3 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                    <p class="text-gray-800">The product arrives <strong>torn, dirty, wet, or covered with lint/hair</strong>.</p>
                                                </div>
                                            </div>
                                            
                                            <div class="bg-white rounded-lg p-4 shadow-sm border border-red-200">
                                                <div class="flex items-start">
                                                    <svg class="w-6 h-6 text-red-500 mr-3 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                    <p class="text-gray-800">The print has visible defects: <strong>blurred, misaligned, or incorrect placement</strong>.</p>
                                                </div>
                                            </div>
                                            
                                            <div class="bg-white rounded-lg p-4 shadow-sm border border-red-200">
                                                <div class="flex items-start">
                                                    <svg class="w-6 h-6 text-red-500 mr-3 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                    <p class="text-gray-800">Print damage occurs after the first wash (<strong>peeling, fading, etc.</strong>).</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Criterion B -->
                                    <div class="bg-gradient-to-br from-orange-50 to-amber-50 border-l-4 border-orange-500 rounded-r-lg p-6">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 w-14 h-14 bg-orange-500 text-white rounded-full flex items-center justify-center mr-4 text-2xl font-bold">B</div>
                                            <div class="flex-1">
                                                <h3 class="text-2xl font-bold text-orange-800 mb-3">b. Incorrect Size</h3>
                                                <div class="bg-white rounded-lg p-4 shadow-sm border border-orange-200">
                                                    <p class="text-gray-800 leading-relaxed">
                                                        Applicable when the item received differs from the size chart by <strong class="text-orange-600">over 1.5 inches</strong> in measurement.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Criterion C -->
                                    <div class="bg-gradient-to-br from-yellow-50 to-amber-50 border-l-4 border-yellow-500 rounded-r-lg p-6">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 w-14 h-14 bg-yellow-500 text-white rounded-full flex items-center justify-center mr-4 text-2xl font-bold">C</div>
                                            <div class="flex-1">
                                                <h3 class="text-2xl font-bold text-yellow-800 mb-3">c. Non-fitting Items</h3>
                                                <div class="bg-white rounded-lg p-4 shadow-sm border border-yellow-200">
                                                    <p class="text-gray-800 leading-relaxed mb-2">
                                                        Only <strong>press-on nail sets</strong> are eligible for returns/exchanges due to fit or quality issues.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Criterion D -->
                                    <div class="bg-gradient-to-br from-pink-50 to-pink-100 border-l-4 border-[#0297FE] rounded-r-lg p-6">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 w-14 h-14 bg-[#0297FE] text-white rounded-full flex items-center justify-center mr-4 text-2xl font-bold">D</div>
                                            <div class="flex-1">
                                                <h3 class="text-2xl font-bold text-[#d6386a] mb-3">d. Shipping-related Damage or Errors</h3>
                                                <div class="bg-white rounded-lg p-4 shadow-sm border border-pink-200">
                                                    <p class="text-gray-800 leading-relaxed mb-3">
                                                        Blu Lavelle supports cases where the order is wrong, damaged, or faulty due to the shipping process.
                                                    </p>
                                                    <div class="bg-pink-100 border border-pink-300 rounded-lg p-3">
                                                        <p class="text-pink-900 text-sm mb-2">
                                                            <strong>💡 Please check your package carefully upon delivery.</strong> If you find any issues:
                                                        </p>
                                                        <ul class="list-disc list-inside text-pink-900 space-y-1">
                                                            <li>Refuse the package immediately, or</li>
                                                            <li>Contact Blu Lavelle within <strong>30 days of delivery</strong> for assistance.</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Eligible Products Section -->
                            <div class="bg-gradient-to-r from-[#0297FE] to-[#d6386a] text-white px-6 py-4 rounded-t-lg mb-0">
                                <h2 class="text-3xl font-bold flex items-center">
                                    <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    3. Products Eligible for Return
                                </h2>
                            </div>
                            <div class="bg-pink-50 border-2 border-pink-300 border-t-0 rounded-b-lg p-6 mb-10">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="bg-white rounded-lg p-5 shadow-md border border-pink-200">
                                        <div class="flex justify-center mb-3">
                                            <div class="w-12 h-12 bg-[#0297FE] rounded-full flex items-center justify-center">
                                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <p class="text-gray-800 text-center">Items that meet the <strong>eligibility criteria</strong> listed above.</p>
                                    </div>
                                    
                                    <div class="bg-white rounded-lg p-5 shadow-md border border-pink-200">
                                        <div class="flex justify-center mb-3">
                                            <div class="w-12 h-12 bg-[#0297FE] rounded-full flex items-center justify-center">
                                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <p class="text-gray-800 text-center">Items that show <strong>no signs of use</strong>, have the <strong>neck label intact</strong>, and remain in the <strong>original packaging</strong>.</p>
                                    </div>
                                    
                                    <div class="bg-white rounded-lg p-5 shadow-md border border-pink-200">
                                        <div class="flex justify-center mb-3">
                                            <div class="w-12 h-12 bg-[#0297FE] rounded-full flex items-center justify-center">
                                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <p class="text-gray-800 text-center">Return/exchange requests submitted <strong>within 30 days</strong> from delivery.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Return Process Section -->
                            <div class="mb-8">
                                <div class="bg-gradient-to-r from-[#0297FE] to-[#d6386a] text-white px-6 py-4 rounded-t-lg">
                                    <h2 class="text-3xl font-bold flex items-center">
                                        <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                        </svg>
                                        4. Return & Exchange Process
                                    </h2>
                                </div>
                                <div class="bg-pink-50 border-2 border-pink-300 border-t-0 rounded-b-lg p-6">
                                    <!-- Step 1 -->
                                    <div class="mb-6">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 w-16 h-16 bg-gradient-to-br from-[#0297FE] to-[#d6386a] text-white rounded-full flex items-center justify-center mr-4 text-2xl font-bold shadow-lg">Step 1</div>
                                            <div class="flex-1 bg-white rounded-lg p-5 shadow-md border border-pink-200">
                                                <h3 class="text-xl font-bold text-pink-900 mb-3">Contact Blu Lavelle</h3>
                                                <p class="text-gray-700 mb-3">When contacting us, please provide:</p>
                                                <div class="space-y-3">
                                                    <div class="flex items-start bg-pink-50 rounded-lg p-3">
                                                        <svg class="w-5 h-5 text-[#0297FE] mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        <p class="text-gray-800">Order information.</p>
                                                    </div>
                                                    <div class="flex items-start bg-pink-50 rounded-lg p-3">
                                                        <svg class="w-5 h-5 text-[#0297FE] mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        <p class="text-gray-800">Photo of the shipping label.</p>
                                                    </div>
                                                    <div class="flex items-start bg-pink-50 rounded-lg p-3">
                                                        <svg class="w-5 h-5 text-[#0297FE] mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        <p class="text-gray-800">Photos clearly showing the damaged/wrong/defective area.</p>
                                                    </div>
                                                    <div class="flex items-start bg-pink-50 rounded-lg p-3">
                                                        <svg class="w-5 h-5 text-[#0297FE] mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        <p class="text-gray-800">Photos showing accurate width & length measurements (if the size is incorrect).</p>
                                                    </div>
                                                    <div class="flex items-start bg-pink-50 rounded-lg p-3">
                                                        <svg class="w-5 h-5 text-[#0297FE] mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        <p class="text-gray-800">Information about the item you want to receive as a replacement.</p>
                                                    </div>
                                                </div>
                                                <div class="mt-4 bg-pink-100 border border-pink-300 rounded-lg p-3">
                                                    <p class="text-pink-900 text-sm">
                                                        <strong>📸 Note:</strong> For orders with multiple items, please provide photos or videos of all items laid flat side by side.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Step 2 -->
                                    <div>
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 w-16 h-16 bg-gradient-to-br from-[#0297FE] to-[#d6386a] text-white rounded-full flex items-center justify-center mr-4 text-2xl font-bold shadow-lg">Step 2</div>
                                            <div class="flex-1 bg-white rounded-lg p-5 shadow-md border border-pink-200">
                                                <h3 class="text-xl font-bold text-pink-900 mb-3">Verification</h3>
                                                <p class="text-gray-700 mb-3">After confirming that your item qualifies for return or exchange, Blu Lavelle will:</p>
                                                <div class="bg-pink-100 border-2 border-[#0297FE] rounded-lg p-4 mb-3">
                                                    <p class="text-pink-900 font-semibold mb-2">
                                                        ✓ Issue a <strong>refund</strong>, or
                                                    </p>
                                                    <p class="text-pink-900 font-semibold">
                                                        ✓ Send a <strong>replacement</strong> within <strong class="text-pink-700">7 business days</strong>.
                                                    </p>
                                                    <p class="text-pink-800 mt-3 font-semibold">
                                                        ✓ You <strong>do not need to send the item back</strong>.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Important Notes -->
                            <div class="bg-gradient-to-br from-amber-100 to-orange-100 border-2 border-amber-500 rounded-lg p-6 mb-8">
                                <h3 class="text-2xl font-bold text-amber-900 mb-4 flex items-center">
                                    <svg class="w-8 h-8 mr-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    Important Notes
                                </h3>
                                <div class="space-y-3">
                                    <div class="bg-white rounded-lg p-4 border-l-4 border-amber-500">
                                        <p class="text-gray-800">
                                            <strong class="text-amber-800">⚠️</strong> Items returned <strong>without Blu Lavelle\'s prior verification will not be supported</strong>.
                                        </p>
                                    </div>
                                    <div class="bg-white rounded-lg p-4 border-l-4 border-amber-500">
                                        <p class="text-gray-800">
                                            <strong class="text-amber-800">💱</strong> Replacements will be issued for items of <strong>equal or greater value</strong> (price differences may apply).
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- International Orders Notice -->
                            <div class="bg-gradient-to-r from-[#0297FE] to-[#d6386a] rounded-lg p-8 text-center text-white">
                                <div class="flex justify-center mb-4">
                                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center">
                                        <svg class="w-10 h-10 text-[#0297FE]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <h3 class="text-2xl font-bold mb-3">International Orders</h3>
                                <p class="text-xl text-pink-100 mb-2">
                                    For <strong>international orders</strong>
                                </p>
                                <div class="bg-white bg-opacity-20 rounded-lg p-4 inline-block">
                                    <p class="text-2xl font-bold">60 DAYS</p>
                                    <p class="text-pink-100">support window from date of delivery</p>
                                </div>
                                <p class="mt-4 text-sm text-pink-100">For international orders, defective or unwanted items are supported within 60 days of delivery.</p>
                            </div>
                        </div>
                    </div>
                </div>',
                'excerpt' => 'Complete returns and exchanges policy - 30-day returns, no restocking fee, easy process for wrong, damaged or non-fitting items',
                'status' => 'published',
                'published_at' => now(),
                'template' => 'default',
                'show_in_menu' => true,
                'menu_title' => 'Returns & Exchanges',
                'sort_order' => 4,
                'meta_title' => 'Returns & Exchanges Policy - Blu Lavelle Easy Returns',
                'meta_description' => 'Easy returns and exchanges within 30 days. No restocking fee. Learn about our return policy for wrong, damaged, or non-fitting items on Blu Lavelle.',
            ],
            [
                'user_id' => $admin->id,
                'title' => 'Refund Policy',
                'slug' => 'refund-policy',
                'content' => '<div class="max-w-6xl mx-auto py-8 px-4">
                    <div class="bg-white rounded-lg shadow-2xl overflow-hidden">
                        <!-- Hero Header -->
                        <div class="bg-gradient-to-r from-[#0297FE] via-[#f89192] to-[#d6386a] text-white px-8 py-12">
                            <h1 class="text-5xl font-bold mb-4">Refund Policy</h1>
                            <p class="text-pink-100 text-xl mb-2">Your satisfaction guaranteed - Full refunds within 30 days</p>
                            <p class="text-pink-200 text-sm">Last updated: ' . now()->format('F d, Y') . '</p>
                        </div>

                        <div class="px-8 py-8">
                            <!-- Introduction -->
                            <div class="bg-gradient-to-br from-pink-50 to-pink-100 border-l-4 border-[#0297FE] rounded-r-lg p-6 mb-8">
                                <p class="text-gray-800 leading-relaxed text-lg mb-4">
                                    Blu Lavelle and most sellers on Blu Lavelle offer <strong>refunds for items within 30 days</strong> from the date of delivery. If there are any problems, please <a href="mailto:support@blulavelle.com" class="text-[#0297FE] hover:text-[#d6386a] font-semibold underline">contact us here</a> to submit your request.
                                </p>
                                <p class="text-gray-800 leading-relaxed text-lg">
                                    We always guarantee that you are satisfied with the orders you have placed on Blu Lavelle. To guarantee your rights when placing orders, please refer to our refund policy under the following conditions:
                                </p>
                            </div>

                            <!-- Refund Scenarios -->
                            <div class="space-y-8 mb-8">
                                <!-- Scenario 1: Wrong/Damaged/Faulty -->
                                <div class="border-2 border-red-300 rounded-lg overflow-hidden">
                                    <div class="bg-gradient-to-r from-red-500 to-rose-500 text-white px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 w-14 h-14 bg-white text-red-600 rounded-full flex items-center justify-center mr-4 text-2xl font-bold shadow-lg">1</div>
                                            <h2 class="text-3xl font-bold">Wrong/Damaged/Faulty Items</h2>
                                        </div>
                                    </div>
                                    <div class="bg-red-50 p-6">
                                        <p class="text-gray-800 font-semibold mb-4 text-lg">We guarantee to assist with cases where customers receive wrong/damaged/faulty items. Includes these cases:</p>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                            <div class="bg-white rounded-lg p-4 shadow-sm border-l-4 border-red-500">
                                                <div class="flex items-start">
                                                    <svg class="w-6 h-6 text-red-500 mr-3 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                    <p class="text-gray-800"><strong>Wrong items:</strong> Product doesn\'t match website description, wrong material, wrong size</p>
                                                </div>
                                            </div>
                                            
                                            <div class="bg-white rounded-lg p-4 shadow-sm border-l-4 border-red-500">
                                                <div class="flex items-start">
                                                    <svg class="w-6 h-6 text-red-500 mr-3 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                    <p class="text-gray-800"><strong>Damaged condition:</strong> Torn, dirty, wet, or hairy fabric</p>
                                                </div>
                                            </div>
                                            
                                            <div class="bg-white rounded-lg p-4 shadow-sm border-l-4 border-red-500">
                                                <div class="flex items-start">
                                                    <svg class="w-6 h-6 text-red-500 mr-3 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                    <p class="text-gray-800"><strong>Print defects:</strong> Visible defects, blurred, or out of place</p>
                                                </div>
                                            </div>
                                            
                                            <div class="bg-white rounded-lg p-4 shadow-sm border-l-4 border-red-500">
                                                <div class="flex items-start">
                                                    <svg class="w-6 h-6 text-red-500 mr-3 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                    <p class="text-gray-800"><strong>Damaged prints:</strong> Damaged or peeled prints after first wash</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-yellow-100 border-l-4 border-yellow-500 p-4 mb-6">
                                            <p class="text-yellow-900">
                                                <strong>⏰ Time limit:</strong> Please contact Customer Support within <strong>30 days of delivery</strong>. Orders over 30 days will not be supported.
                                            </p>
                                        </div>

                                        <div class="bg-white rounded-lg p-5 shadow-md border border-red-200">
                                            <h3 class="font-bold text-red-900 mb-3 text-lg">Required Information:</h3>
                                            <div class="space-y-2">
                                                <div class="flex items-start">
                                                    <svg class="w-5 h-5 text-red-500 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <p class="text-gray-800">Order information to confirm the order</p>
                                                </div>
                                                <div class="flex items-start">
                                                    <svg class="w-5 h-5 text-red-500 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <p class="text-gray-800">Photos of the packaging label</p>
                                                </div>
                                                <div class="flex items-start">
                                                    <svg class="w-5 h-5 text-red-500 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <p class="text-gray-800">Photos of the items and size tag/neck label in a frame</p>
                                                </div>
                                                <div class="flex items-start">
                                                    <svg class="w-5 h-5 text-red-500 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <p class="text-gray-800">Photos clearly showing damaged/wrong/faulty parts different from website description</p>
                                                </div>
                                            </div>
                                            <div class="mt-4 bg-red-100 border border-red-300 rounded-lg p-3">
                                                <p class="text-red-900 text-sm">
                                                    <strong>📸 Note:</strong> For orders with multiple items, provide photos/videos of products placed side by side on a flat surface.
                                                </p>
                                            </div>
                                        </div>

                                        <div class="mt-6 bg-pink-100 border-2 border-[#0297FE] rounded-lg p-5">
                                            <p class="text-pink-900 font-semibold text-lg">
                                                ✓ After we confirm your product is eligible, you will receive a <strong>refund immediately</strong> to the account you used for payment.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Scenario 2: Wrong Size -->
                                <div class="border-2 border-orange-300 rounded-lg overflow-hidden">
                                    <div class="bg-gradient-to-r from-orange-500 to-amber-500 text-white px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 w-14 h-14 bg-white text-orange-600 rounded-full flex items-center justify-center mr-4 text-2xl font-bold shadow-lg">2</div>
                                            <h2 class="text-3xl font-bold">Wrong Size from the One Ordered</h2>
                                        </div>
                                    </div>
                                    <div class="bg-orange-50 p-6">
                                        <p class="text-gray-800 font-semibold mb-4 text-lg">
                                            We guarantee to assist with cases where customers receive the wrong size product. Specifically, products with wrong measurements compared to the size guide <strong class="text-orange-600">(A difference of over 1.5")</strong> from standard measurements.
                                        </p>

                                        <div class="bg-yellow-100 border-l-4 border-yellow-500 p-4 mb-6">
                                            <p class="text-yellow-900">
                                                <strong>⏰ Time limit:</strong> Please contact Customer Support within <strong>30 days of delivery</strong>. Orders over 30 days will not be supported.
                                            </p>
                                        </div>

                                        <div class="bg-white rounded-lg p-5 shadow-md border border-orange-200">
                                            <h3 class="font-bold text-orange-900 mb-3 text-lg">Required Information:</h3>
                                            <div class="space-y-2">
                                                <div class="flex items-start">
                                                    <svg class="w-5 h-5 text-orange-500 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <p class="text-gray-800">Order information to confirm the order</p>
                                                </div>
                                                <div class="flex items-start">
                                                    <svg class="w-5 h-5 text-orange-500 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <p class="text-gray-800">Photos of the items and size tag/neck label in a frame</p>
                                                </div>
                                                <div class="flex items-start">
                                                    <svg class="w-5 h-5 text-orange-500 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <p class="text-gray-800">Photos clearly showing actual measurements: width, length (Please use measuring tape)</p>
                                                </div>
                                            </div>
                                            <div class="mt-4 bg-orange-100 border border-orange-300 rounded-lg p-3">
                                                <p class="text-orange-900 text-sm">
                                                    <strong>📸 Note:</strong> For orders with multiple items, provide photos/videos of products placed side by side on a flat surface.
                                                </p>
                                            </div>
                                        </div>

                                        <div class="mt-6 bg-pink-100 border-2 border-[#0297FE] rounded-lg p-5">
                                            <p class="text-pink-900 font-semibold text-lg">
                                                ✓ After we confirm measurements (unused) differ over 1.5" from standard, you will receive a <strong>refund immediately</strong> to the account you used for payment.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Scenario 3: Lost Items -->
                                <div class="border-2 border-pink-300 rounded-lg overflow-hidden">
                                    <div class="bg-gradient-to-r from-[#0297FE] to-[#d6386a] text-white px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 w-14 h-14 bg-white text-[#0297FE] rounded-full flex items-center justify-center mr-4 text-2xl font-bold shadow-lg">3</div>
                                            <h2 class="text-3xl font-bold">Lost Items in Shipping</h2>
                                        </div>
                                    </div>
                                    <div class="bg-pink-50 p-6">
                                        <p class="text-gray-800 font-semibold mb-4 text-lg">
                                            We guarantee to assist with cases where your order is lost in the shipping process.
                                        </p>

                                        <div class="bg-pink-100 border-l-4 border-[#0297FE] p-4 mb-6">
                                            <p class="text-pink-900">
                                                <strong>📧 Important:</strong> As soon as tracking reports show "delivered", we will email you to confirm receipt. Please check your mailbox, security cameras, neighbors, and contact local post office before claiming lost items.
                                            </p>
                                        </div>

                                        <div class="bg-yellow-100 border-l-4 border-yellow-500 p-4 mb-6">
                                            <p class="text-yellow-900">
                                                <strong>⏰ Time limit:</strong> Contact us immediately within <strong>30 days of delivery</strong> if you haven\'t received the package. Orders over 30 days will not be supported.
                                            </p>
                                        </div>

                                        <div class="bg-white rounded-lg p-5 shadow-md border border-pink-200">
                                            <h3 class="font-bold text-pink-900 mb-3 text-lg">Required Information:</h3>
                                            <div class="space-y-2">
                                                <div class="flex items-start">
                                                    <svg class="w-5 h-5 text-[#0297FE] mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <p class="text-gray-800">Order information</p>
                                                </div>
                                                <div class="flex items-start">
                                                    <svg class="w-5 h-5 text-[#0297FE] mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <p class="text-gray-800">Verified shipping address</p>
                                                </div>
                                                <div class="flex items-start">
                                                    <svg class="w-5 h-5 text-[#0297FE] mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <p class="text-gray-800">Verification from your local post office that you haven\'t received the package</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-6 bg-pink-100 border-2 border-[#0297FE] rounded-lg p-5">
                                            <p class="text-pink-900 font-semibold text-lg">
                                                ✓ After sending all necessary information, you will receive a <strong>refund immediately</strong> to the account you used for payment.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Scenario 4: Cancellation -->
                                <div class="border-2 border-pink-300 rounded-lg overflow-hidden">
                                    <div class="bg-gradient-to-r from-pink-500 to-rose-500 text-white px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 w-14 h-14 bg-white text-pink-600 rounded-full flex items-center justify-center mr-4 text-2xl font-bold shadow-lg">4</div>
                                            <h2 class="text-3xl font-bold">Order Cancellation</h2>
                                        </div>
                                    </div>
                                    <div class="bg-pink-50 p-6">
                                        <div class="bg-white rounded-lg p-6 shadow-md border border-pink-200">
                                            <div class="flex items-start mb-4">
                                                <div class="flex-shrink-0 w-16 h-16 bg-gradient-to-br from-pink-500 to-rose-500 text-white rounded-full flex items-center justify-center mr-4">
                                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </div>
                                                <div class="flex-1">
                                                    <h3 class="text-2xl font-bold text-pink-900 mb-2">4 Hours Window</h3>
                                                    <p class="text-gray-800 leading-relaxed">
                                                        After making your purchase, you have <strong class="text-pink-600">4 hours</strong> to cancel the order. Please submit a cancellation request, and your order will be canceled with a full refund to your card.
                                                    </p>
                                                </div>
                                            </div>
                                            
                                            <div class="bg-red-100 border-2 border-red-400 rounded-lg p-4">
                                                <p class="text-red-900 font-semibold">
                                                    ⚠️ Once 4 hours have passed, Blu Lavelle <strong>refuses to support</strong> order cancellation or modification requests.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Order Information Note -->
                            <div class="bg-gradient-to-br from-slate-100 to-gray-100 border-l-4 border-slate-500 rounded-r-lg p-6 mb-8">
                                <h3 class="text-xl font-bold text-slate-800 mb-3 flex items-center">
                                    <svg class="w-6 h-6 mr-2 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Order Information Includes:
                                </h3>
                                <div class="bg-white rounded-lg p-4 space-y-2">
                                    <p class="text-gray-800"><strong>•</strong> Order code</p>
                                    <p class="text-gray-800"><strong>•</strong> Shipping address</p>
                                    <p class="text-gray-800"><strong>•</strong> Recipient\'s information: Full name, Phone number, Email address</p>
                                </div>
                            </div>

                            <!-- International Orders Notice -->
                            <div class="bg-gradient-to-r from-[#0297FE] to-[#d6386a] rounded-lg p-8 text-center text-white mb-8">
                                <div class="flex justify-center mb-4">
                                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center">
                                        <svg class="w-10 h-10 text-[#0297FE]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <h3 class="text-2xl font-bold mb-3">International Orders</h3>
                                <p class="text-xl text-pink-100 mb-2">
                                    For all orders shipped <strong>outside the US</strong>
                                </p>
                                <div class="bg-white bg-opacity-20 rounded-lg p-4 inline-block">
                                    <p class="text-3xl font-bold">60 DAYS</p>
                                    <p class="text-pink-100">support window from date of delivery</p>
                                </div>
                                <p class="mt-4 text-sm text-pink-100">We support all defective or unwanted orders within 60 days</p>
                            </div>

                            <!-- Contact Footer -->
                            <div class="bg-gradient-to-r from-[#0297FE] to-[#d6386a] rounded-lg p-8 text-center text-white">
                                <div class="flex justify-center mb-4">
                                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center">
                                        <svg class="w-10 h-10 text-[#0297FE]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <h3 class="text-2xl font-bold mb-3">Questions About Refunds?</h3>
                                <p class="text-lg text-pink-100 mb-5">
                                    If you have any questions regarding our refund policy, please contact our Customer Support Team for quick response and support.
                                </p>
                                <a href="mailto:support@blulavelle.com" class="inline-flex items-center px-8 py-3 bg-white text-[#0297FE] font-bold rounded-lg shadow-lg hover:shadow-xl transition duration-200">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    support@blulavelle.com
                                </a>
                            </div>
                        </div>
                    </div>
                </div>',
                'excerpt' => 'Complete refund policy - 30-day refunds for wrong, damaged, wrong size or lost items. 4-hour cancellation window.',
                'status' => 'published',
                'published_at' => now(),
                'template' => 'default',
                'show_in_menu' => true,
                'menu_title' => 'Refund Policy',
                'sort_order' => 5,
                'meta_title' => 'Refund Policy - Blu Lavelle Full Refund Guarantee',
                'meta_description' => 'Full refund policy within 30 days for wrong, damaged, or lost items. 4-hour cancellation window. Learn about our refund procedures on Blu Lavelle.',
            ],
            [
                'user_id' => $admin->id,
                'title' => 'Cancel or Change Order',
                'slug' => 'cancelchange-order',
                'content' => '<div class="max-w-5xl mx-auto py-8 px-4">
                    <div class="bg-white rounded-lg shadow-2xl overflow-hidden">
                        <!-- Hero Header -->
                        <div class="bg-gradient-to-r from-orange-600 via-red-600 to-pink-600 text-white px-8 py-12">
                            <h1 class="text-5xl font-bold mb-4">Cancel or Change Order</h1>
                            <p class="text-orange-100 text-xl mb-2">Need to make changes? You have 4 hours!</p>
                            <p class="text-orange-200 text-sm">Last updated: ' . now()->format('F d, Y') . '</p>
                        </div>

                        <div class="px-8 py-8">
                            <!-- 4 Hours Window - Main Feature -->
                            <div class="bg-gradient-to-br from-amber-100 to-orange-100 border-2 border-orange-400 rounded-lg p-8 mb-8">
                                <div class="flex items-center justify-center mb-6">
                                    <div class="relative">
                                        <div class="w-32 h-32 bg-gradient-to-br from-orange-500 to-red-500 rounded-full flex items-center justify-center shadow-2xl">
                                            <svg class="w-20 h-20 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <div class="absolute -top-2 -right-2 w-12 h-12 bg-yellow-400 rounded-full flex items-center justify-center font-bold text-2xl text-orange-900 shadow-lg animate-pulse">
                                            4
                                        </div>
                                    </div>
                                </div>
                                <h2 class="text-4xl font-bold text-center text-orange-900 mb-4">4 Hours Window</h2>
                                <p class="text-center text-gray-800 text-xl leading-relaxed">
                                    You have <strong class="text-orange-600">4 hours</strong> to cancel or change your order after placing it
                                </p>
                            </div>

                            <!-- What Can Be Changed -->
                            <div class="mb-8">
                                <div class="bg-gradient-to-r from-[#0297FE] to-[#d6386a] text-white px-6 py-4 rounded-t-lg">
                                    <h2 class="text-3xl font-bold flex items-center">
                                        <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        What Can Be Changed?
                                    </h2>
                                </div>
                                <div class="bg-pink-50 border-2 border-pink-300 border-t-0 rounded-b-lg p-6">
                                    <p class="text-gray-800 font-semibold mb-4 text-lg">Within 4 hours, you can modify the following:</p>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="bg-white rounded-lg p-5 shadow-md border-l-4 border-[#0297FE]">
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 w-12 h-12 bg-[#0297FE] rounded-full flex items-center justify-center mr-3">
                                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h3 class="font-bold text-pink-900 text-lg mb-1">Size</h3>
                                                    <p class="text-gray-700 text-sm">Change product size</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-white rounded-lg p-5 shadow-md border-l-4 border-cyan-500">
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 w-12 h-12 bg-[#0297FE] rounded-full flex items-center justify-center mr-3">
                                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h3 class="font-bold text-pink-900 text-lg mb-1">Color</h3>
                                                    <p class="text-gray-700 text-sm">Change product color</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-white rounded-lg p-5 shadow-md border-l-4 border-[#0297FE]">
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 w-12 h-12 bg-[#0297FE] rounded-full flex items-center justify-center mr-3">
                                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h3 class="font-bold text-pink-900 text-lg mb-1">Quantity</h3>
                                                    <p class="text-gray-700 text-sm">Change order quantity</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-white rounded-lg p-5 shadow-md border-l-4 border-purple-500">
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 w-12 h-12 bg-[#0297FE] rounded-full flex items-center justify-center mr-3">
                                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h3 class="font-bold text-pink-900 text-lg mb-1">Shipping Address</h3>
                                                    <p class="text-gray-700 text-sm">Update delivery address</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- How to Cancel or Change -->
                            <div class="mb-8">
                                <div class="bg-gradient-to-r from-[#0297FE] to-[#d6386a] text-white px-6 py-4 rounded-t-lg">
                                    <h2 class="text-3xl font-bold flex items-center">
                                        <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        How to Cancel or Change Your Order
                                    </h2>
                                </div>
                                <div class="bg-pink-50 border-2 border-pink-300 border-t-0 rounded-b-lg p-6">
                                    <div class="space-y-4">
                                        <div class="flex items-start bg-white rounded-lg p-5 shadow-md">
                                            <div class="flex-shrink-0 w-14 h-14 bg-gradient-to-br from-[#0297FE] to-[#d6386a] text-white rounded-full flex items-center justify-center mr-4 text-2xl font-bold shadow-lg">1</div>
                                            <div class="flex-1">
                                                <h3 class="text-xl font-bold text-pink-900 mb-2">Go to "Contact Us"</h3>
                                                <p class="text-gray-700">Navigate to our Contact Us page or customer support section</p>
                                            </div>
                                        </div>

                                        <div class="flex items-start bg-white rounded-lg p-5 shadow-md">
                                            <div class="flex-shrink-0 w-14 h-14 bg-gradient-to-br from-[#0297FE] to-[#d6386a] text-white rounded-full flex items-center justify-center mr-4 text-2xl font-bold shadow-lg">2</div>
                                            <div class="flex-1">
                                                <h3 class="text-xl font-bold text-pink-900 mb-2">Create a Ticket</h3>
                                                <p class="text-gray-700">Submit a support ticket with your order details and requested changes</p>
                                            </div>
                                        </div>

                                        <div class="flex items-start bg-white rounded-lg p-5 shadow-md">
                                            <div class="flex-shrink-0 w-14 h-14 bg-gradient-to-br from-pink-500 to-rose-500 text-white rounded-full flex items-center justify-center mr-4 text-2xl font-bold shadow-lg">3</div>
                                            <div class="flex-1">
                                                <h3 class="text-xl font-bold text-pink-900 mb-2">Reach Customer Support</h3>
                                                <p class="text-gray-700 mb-2">Or contact our customer support team directly:</p>
                                                <a href="mailto:support@blulavelle.com" class="inline-flex items-center text-[#0297FE] hover:text-[#d6386a] font-semibold underline">
                                                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                    </svg>
                                                    support@blulavelle.com
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Warning - After 4 Hours -->
                            <div class="bg-red-100 border-2 border-red-500 rounded-lg p-8 mb-8">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 w-16 h-16 bg-red-500 rounded-full flex items-center justify-center mr-4">
                                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-2xl font-bold text-red-800 mb-3">⚠️ Important: After 4 Hours</h3>
                                        <p class="text-red-900 font-semibold text-lg mb-3">
                                            When it is more than <strong>4 hours after placing an order</strong>, Blu Lavelle <strong>refuses to support</strong> order cancellation or order modification requests.
                                        </p>
                                        <div class="bg-white rounded-lg p-4 border-l-4 border-red-500">
                                            <p class="text-gray-800">
                                                Once the 4-hour window has passed, your order will enter production and cannot be changed or cancelled.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Delivery Address Responsibility -->
                            <div class="bg-gradient-to-br from-yellow-50 to-amber-50 border-2 border-yellow-400 rounded-lg p-6 mb-8">
                                <h3 class="text-2xl font-bold text-yellow-900 mb-4 flex items-center">
                                    <svg class="w-8 h-8 mr-3 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    Delivery Address Responsibility
                                </h3>
                                <div class="bg-white rounded-lg p-5 shadow-md border-l-4 border-yellow-500">
                                    <p class="text-gray-800 leading-relaxed mb-3">
                                        It is the <strong>customer\'s responsibility</strong> to ensure the product delivery address is correct.
                                    </p>
                                    <div class="bg-yellow-100 border border-yellow-300 rounded-lg p-4">
                                        <p class="text-yellow-900 font-semibold">
                                            <strong>⚠️ Please Note:</strong> Blu Lavelle takes <strong>no responsibility</strong> for any product a customer does not receive because of errors in the delivery address given to us.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Tips -->
                            <div class="bg-gradient-to-br from-pink-50 to-pink-100 border-l-4 border-[#0297FE] rounded-r-lg p-6">
                                <h3 class="text-xl font-bold text-pink-900 mb-4 flex items-center">
                                    <svg class="w-7 h-7 mr-2 text-[#0297FE]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                    </svg>
                                    Quick Tips
                                </h3>
                                <div class="bg-white rounded-lg p-5 space-y-3">
                                    <div class="flex items-start">
                                        <svg class="w-6 h-6 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <p class="text-gray-800">Double-check your order details before submitting</p>
                                    </div>
                                    <div class="flex items-start">
                                        <svg class="w-6 h-6 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <p class="text-gray-800">Verify your shipping address is complete and accurate</p>
                                    </div>
                                    <div class="flex items-start">
                                        <svg class="w-6 h-6 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <p class="text-gray-800">Act quickly if you need to make changes - don\'t wait until the last minute</p>
                                    </div>
                                    <div class="flex items-start">
                                        <svg class="w-6 h-6 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <p class="text-gray-800">Save your order confirmation email for reference</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>',
                'excerpt' => '4-hour window to cancel or change your order - size, color, quantity, or shipping address. Quick and easy modification process.',
                'status' => 'published',
                'published_at' => now(),
                'template' => 'default',
                'show_in_menu' => true,
                'menu_title' => 'Cancel/Change Order',
                'sort_order' => 6,
                'meta_title' => 'Cancel or Change Order - Blu Lavelle 4-Hour Window',
                'meta_description' => 'Need to cancel or change your order? You have 4 hours to modify size, color, quantity, or shipping address. Learn how on Blu Lavelle.',
            ],
            [
                'user_id' => $admin->id,
                'title' => 'About Us',
                'slug' => 'about-us',
                'content' => '<div class="max-w-7xl mx-auto py-8 px-4">
                    <div class="bg-white rounded-lg shadow-2xl overflow-hidden">
                        <!-- Hero Header -->
                        <div class="relative bg-gradient-to-r from-[#0297FE] via-[#f89192] to-[#d6386a] text-white px-8 py-16">
                            <div class="absolute inset-0 bg-black opacity-10"></div>
                            <div class="relative z-10 text-center">
                                <h1 class="text-6xl font-bold mb-4">Welcome to Blu Lavelle</h1>
                                <p class="text-3xl text-pink-100 mb-6">Press-on Nails for Every Style</p>
                                <div class="flex justify-center mb-4">
                                    <div class="w-24 h-1 bg-white rounded"></div>
                                </div>
                                <p class="text-xl text-pink-100 max-w-4xl mx-auto leading-relaxed">
                                    Discover premium press-on nail sets designed to elevate your look—anytime, anywhere
                                </p>
                            </div>
                        </div>

                        <div class="px-8 py-12">
                            <!-- Introduction -->
                            <div class="max-w-5xl mx-auto mb-16">
                                <div class="text-center mb-12">
                                    <p class="text-2xl text-gray-700 leading-relaxed mb-6">
                                        At <strong class="text-[#0297FE]">Blu Lavelle</strong>, we craft and curate press-on nails that help you express yourself with confidence. From everyday minimal sets to bold statement designs, our goal is to make salon‑quality nails easy, fast, and accessible—right at home.
                                    </p>
                                    <div class="bg-gradient-to-r from-pink-50 to-pink-100 rounded-lg p-8 border-l-4 border-[#0297FE]">
                                        <p class="text-xl text-gray-800 font-semibold">
                                            No salon appointments. No waiting. <span class="text-[#0297FE]">Just beautiful nails made to fit your lifestyle.</span>
                                        </p>
                                        <p class="text-lg text-gray-700 mt-3">
                                            Start your journey today and find a set that feels uniquely yours.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- About Us Section -->
                            <div class="mb-16">
                                <div class="bg-gradient-to-r from-[#0297FE] to-[#d6386a] text-white px-8 py-6 rounded-t-lg">
                                    <h2 class="text-4xl font-bold flex items-center justify-center">
                                        <svg class="w-10 h-10 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                        Our Story
                                    </h2>
                                </div>
                                <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-b-lg p-8 border-2 border-pink-200 border-t-0">
                                    <div class="max-w-4xl mx-auto">
                                        <p class="text-lg text-gray-800 leading-relaxed mb-6">
                                            Blu Lavelle was created for people who love nails but don\'t always have time for the salon. We focus on comfort, durability, and trend‑forward designs so you can get a clean, polished look in minutes—whether you\'re getting ready for work, a party, or a weekend getaway.
                                            <br>No salon appointments. No waiting. Just beautiful nails made to fit your lifestyle.
                                            <br>Start your journey today and discover nails that feel uniquely yours.
                                        </p>
                                        <p class="text-lg text-gray-800 leading-relaxed mb-6">
                                            At Blu Lavelle, we celebrate <strong class="text-[#0297FE]">creativity</strong>, <strong class="text-[#0297FE]">quality</strong>, and <strong class="text-[#0297FE]">confidence</strong>. Every set is made with attention to detail—from shape and fit to color and finish—so you can switch styles whenever you want and feel great doing it.
                                        </p>
                                        <div class="bg-white rounded-lg p-6 shadow-lg border-l-4 border-purple-500">
                                            <p class="text-2xl font-bold text-center text-gray-800">
                                                Our mission is simple: <span class="text-[#0297FE]">to make salon‑quality nails effortless for everyone.</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Us Section -->
                            <div class="mb-16">
                                <div class="bg-gradient-to-r from-[#0297FE] to-[#d6386a] text-white px-8 py-6 rounded-t-lg">
                                    <h2 class="text-4xl font-bold flex items-center justify-center">
                                        <svg class="w-10 h-10 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        Contact Us
                                    </h2>
                                </div>
                                <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-b-lg p-8 border-2 border-pink-200 border-t-0">
                                    <p class="text-center text-2xl font-semibold text-gray-800 mb-8">We\'d Love to Hear From You!</p>
                                    <p class="text-center text-lg text-gray-700 mb-8 max-w-3xl mx-auto">
                                        Have a question, need assistance, or just want to say hello? We\'re here to help. Reach out to us anytime using the contact options below:
                                    </p>

                                    <!-- Contact Methods -->
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                                        <div class="bg-white rounded-lg p-6 shadow-lg text-center border-2 border-pink-200 hover:border-blue-400 transition duration-200">
                                            <div class="flex justify-center mb-4">
                                                <div class="w-16 h-16 bg-[#0297FE] rounded-full flex items-center justify-center">
                                                    <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <h3 class="text-xl font-bold text-gray-800 mb-2">Email</h3>
                                            <a href="mailto:admin@blulavelle.com" class="text-[#0297FE] hover:text-[#d6386a] font-semibold text-lg underline">admin@blulavelle.com</a>
                                        </div>

                                        <div class="bg-white rounded-lg p-6 shadow-lg text-center border-2 border-pink-200 hover:border-[#0297FE] transition duration-200">
                                            <div class="flex justify-center mb-4">
                                                <div class="w-16 h-16 bg-[#0297FE] rounded-full flex items-center justify-center">
                                                    <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <h3 class="text-xl font-bold text-gray-800 mb-2">Phone</h3>
                                            <a href="tel:0767383676" class="text-[#0297FE] hover:text-pink-800 font-semibold text-lg">0767 383 676</a>
                                        </div>

                                        <div class="bg-white rounded-lg p-6 shadow-lg text-center border-2 border-pink-200 hover:border-purple-400 transition duration-200">
                                            <div class="flex justify-center mb-4">
                                                <div class="w-16 h-16 bg-[#0297FE] rounded-full flex items-center justify-center">
                                                    <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <h3 class="text-xl font-bold text-gray-800 mb-2">Live Chat</h3>
                                            <p class="text-gray-600">Available on our website for real-time assistance</p>
                                        </div>
                                    </div>

                                    <!-- Warehouse Addresses -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="bg-white rounded-lg p-6 shadow-lg border-l-4 border-red-500">
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 w-12 h-12 bg-red-500 rounded-full flex items-center justify-center mr-4">
                                                    <span class="text-white font-bold text-xl">🇺🇸</span>
                                                </div>
                                                <div>
                                                    <h3 class="text-xl font-bold text-gray-800 mb-2">US Warehouse</h3>
                                                    <p class="text-gray-700">
                                                        1301 E ARAPAHO RD, STE 101<br>
                                                        RICHARDSON, TX 75081<br>
                                                        United States
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-white rounded-lg p-6 shadow-lg border-l-4 border-[#0297FE]">
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 w-12 h-12 bg-[#0297FE] rounded-full flex items-center justify-center mr-4">
                                                    <span class="text-white font-bold text-xl">🇬🇧</span>
                                                </div>
                                                <div>
                                                    <h3 class="text-xl font-bold text-gray-800 mb-2">UK Warehouse</h3>
                                                    <p class="text-gray-700">
                                                        3 Kincraig Rd<br>
                                                        Blackpool FY2 0FY<br>
                                                        United Kingdom
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-8 bg-pink-100 border-2 border-pink-300 rounded-lg p-6 text-center">
                                        <p class="text-pink-900 font-semibold text-lg">
                                            We\'re committed to making your Blu Lavelle experience extraordinary. Let\'s connect!
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Legal Information Section -->
                            <div class="mb-8">
                                <div class="bg-gradient-to-r from-slate-700 to-gray-700 text-white px-8 py-6 rounded-t-lg">
                                    <h2 class="text-4xl font-bold flex items-center justify-center">
                                        <svg class="w-10 h-10 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Legal Information
                                    </h2>
                                </div>
                                <div class="bg-gradient-to-br from-slate-50 to-gray-50 rounded-b-lg p-8 border-2 border-slate-300 border-t-0">
                                    <p class="text-center text-xl font-semibold text-gray-800 mb-8">The website is jointly operated by:</p>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <!-- Vietnam Company -->
                                        <div class="bg-white rounded-lg p-6 shadow-lg border-2 border-red-200">
                                            <div class="flex items-start mb-3">
                                                <span class="text-3xl mr-3">🇻🇳</span>
                                                <h3 class="text-xl font-bold text-gray-800">Vietnam</h3>
                                            </div>
                                            <div class="bg-red-50 rounded-lg p-4">
                                                <p class="font-bold text-red-900 mb-2">HM FULFILL COMPANY LIMITED</p>
                                                <p class="text-gray-700 text-sm">
                                                    63/9Đ, Ap Chanh 1, Tan Xuan<br>
                                                    Hoc Mon, Ho Chi Minh City<br>
                                                    700000, Vietnam
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Hong Kong Company -->
                                        <div class="bg-white rounded-lg p-6 shadow-lg border-2 border-yellow-200">
                                            <div class="flex items-start mb-3">
                                                <span class="text-3xl mr-3">🇭🇰</span>
                                                <h3 class="text-xl font-bold text-gray-800">Hong Kong</h3>
                                            </div>
                                            <div class="bg-yellow-50 rounded-lg p-4">
                                                <p class="font-bold text-yellow-900 mb-2">BLUE STAR TRADING LIMITED</p>
                                                <p class="text-gray-700 text-sm">
                                                    RM C, 6/F, WORLD TRUST TOWER<br>
                                                    50 STANLEY STREET<br>
                                                    CENTRAL, HONG KONG
                                                </p>
                                            </div>
                                        </div>

                                        <!-- UK Company -->
                                        <div class="bg-white rounded-lg p-6 shadow-lg border-2 border-pink-200">
                                            <div class="flex items-start mb-3">
                                                <span class="text-3xl mr-3">🇬🇧</span>
                                                <h3 class="text-xl font-bold text-gray-800">United Kingdom</h3>
                                            </div>
                                            <div class="bg-pink-50 rounded-lg p-4">
                                                <p class="font-bold text-pink-900 mb-2">Blu Lavelle LTD</p>
                                                <p class="text-gray-700 text-sm mb-2">
                                                    <strong>Company Number:</strong> 16342615
                                                </p>
                                                <p class="text-gray-700 text-sm">
                                                    71-75 Shelton Street<br>
                                                    Covent Garden, London<br>
                                                    WC2H 9JQ, United Kingdom
                                                </p>
                                            </div>
                                        </div>

                                        <!-- US Company -->
                                        <div class="bg-white rounded-lg p-6 shadow-lg border-2 border-red-200">
                                            <div class="flex items-start mb-3">
                                                <span class="text-3xl mr-3">🇺🇸</span>
                                                <h3 class="text-xl font-bold text-gray-800">United States</h3>
                                            </div>
                                            <div class="bg-red-50 rounded-lg p-4">
                                                <p class="font-bold text-red-900 mb-2">Blu Lavelle LLC</p>
                                                <p class="text-gray-700 text-sm">
                                                    5900 BALCONES DR STE 100<br>
                                                    AUSTIN, TX 78731<br>
                                                    United States
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Call to Action -->
                            <div class="bg-gradient-to-r from-[#0297FE] via-[#f89192] to-[#d6386a] rounded-lg p-10 text-center text-white">
                                <h3 class="text-3xl font-bold mb-4">Find Your Next Nail Set</h3>
                                <p class="text-xl text-pink-100 mb-6 max-w-3xl mx-auto">
                                    Explore our newest press-on nail designs and choose a style that matches your vibe today.
                                </p>
                                <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                                    <a href="/" class="inline-flex items-center px-8 py-4 bg-white text-[#0297FE] font-bold rounded-lg shadow-xl hover:shadow-2xl transition duration-200 text-lg">
                                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                        </svg>
                                        Shop Nails
                                    </a>
                                    <a href="mailto:admin@blulavelle.com" class="inline-flex items-center px-8 py-4 bg-transparent border-2 border-white text-white font-bold rounded-lg hover:bg-white hover:text-[#0297FE] transition duration-200 text-lg">
                                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        Contact Us
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>',
                'excerpt' => 'Learn about Blu Lavelle - your destination for press-on nails. Discover our story, mission, and contact information.',
                'status' => 'published',
                'published_at' => now(),
                'template' => 'default',
                'show_in_menu' => true,
                'menu_title' => 'About',
                'sort_order' => 7,
                'meta_title' => 'About Us - Blu Lavelle',
                'meta_description' => 'Discover Blu Lavelle - your go-to for stylish press-on nails. Find unique designs and quality nail products.',
            ],
            [
                'user_id' => $admin->id,
                'title' => 'Contact Us',
                'slug' => 'contact-us-2',
                'content' => '<div class="max-w-7xl mx-auto py-8 px-4">
                    <div class="bg-white rounded-lg shadow-2xl overflow-hidden">
                        <!-- Hero Header -->
                        <div class="bg-gradient-to-r from-[#0297FE] via-[#f89192] to-[#d6386a] text-white px-8 py-16">
                            <div class="text-center">
                                <div class="flex justify-center mb-6">
                                    <div class="w-24 h-24 bg-white bg-opacity-20 rounded-full flex items-center justify-center backdrop-blur-sm">
                                        <svg class="w-14 h-14 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <h1 class="text-6xl font-bold mb-4">Contact Us</h1>
                                <p class="text-2xl text-green-100">We\'re Here to Help!</p>
                                <p class="text-lg text-teal-100 mt-4 max-w-3xl mx-auto">
                                    Get in touch with us through any of the channels below. We\'re available worldwide to support your needs.
                                </p>
                            </div>
                        </div>

                        <div class="px-8 py-12">
                            <!-- Contact Methods -->
                            <div class="mb-16">
                                <div class="text-center mb-10">
                                    <h2 class="text-4xl font-bold text-gray-800 mb-3">Get In Touch</h2>
                                    <div class="flex justify-center">
                                        <div class="w-20 h-1 bg-gradient-to-r from-[#0297FE] to-[#d6386a] rounded"></div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                                    <!-- Email -->
                                    <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-lg p-6 shadow-lg border-2 border-pink-200 hover:border-blue-400 transition duration-200">
                                        <div class="flex justify-center mb-4">
                                            <div class="w-16 h-16 bg-[#0297FE] rounded-full flex items-center justify-center shadow-lg">
                                                <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <h3 class="text-xl font-bold text-center text-gray-800 mb-2">Email</h3>
                                        <p class="text-center">
                                            <a href="mailto:admin@blulavelle.com" class="text-[#0297FE] hover:text-[#d6386a] font-semibold text-lg underline break-all">admin@blulavelle.com</a>
                                        </p>
                                    </div>

                                    <!-- Phone -->
                                    <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-lg p-6 shadow-lg border-2 border-pink-200 hover:border-[#0297FE] transition duration-200">
                                        <div class="flex justify-center mb-4">
                                            <div class="w-16 h-16 bg-[#0297FE] rounded-full flex items-center justify-center shadow-lg">
                                                <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <h3 class="text-xl font-bold text-center text-gray-800 mb-2">Call Us</h3>
                                        <p class="text-center">
                                            <a href="tel:+18563782798" class="text-[#0297FE] hover:text-pink-800 font-semibold text-lg">+1 856-378-2798</a>
                                        </p>
                                    </div>

                                    <!-- iMessage -->
                                    <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-lg p-6 shadow-lg border-2 border-pink-200 hover:border-purple-400 transition duration-200">
                                        <div class="flex justify-center mb-4">
                                            <div class="w-16 h-16 bg-[#0297FE] rounded-full flex items-center justify-center shadow-lg">
                                                <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <h3 class="text-xl font-bold text-center text-gray-800 mb-2">iMessage</h3>
                                        <p class="text-center text-[#0297FE] font-semibold text-lg">+1 856-378-2798</p>
                                    </div>

                                    <!-- WhatsApp -->
                                    <div class="bg-gradient-to-br from-green-50 to-lime-50 rounded-lg p-6 shadow-lg border-2 border-pink-200 hover:border-[#0297FE] transition duration-200">
                                        <div class="flex justify-center mb-4">
                                            <div class="w-16 h-16 bg-[#0297FE] rounded-full flex items-center justify-center shadow-lg">
                                                <svg class="w-9 h-9 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <h3 class="text-xl font-bold text-center text-gray-800 mb-2">WhatsApp</h3>
                                        <p class="text-center">
                                            <a href="https://wa.me/18563782798" target="_blank" class="text-[#0297FE] hover:text-pink-800 font-semibold text-lg">+1 856-378-2798</a>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Business Addresses -->
                            <div class="mb-16">
                                <div class="text-center mb-10">
                                    <h2 class="text-4xl font-bold text-gray-800 mb-3">Our Global Offices</h2>
                                    <div class="flex justify-center">
                                        <div class="w-20 h-1 bg-gradient-to-r from-[#0297FE] to-[#d6386a] rounded"></div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- UK Office -->
                                    <div class="bg-white rounded-lg p-6 shadow-xl border-2 border-pink-200 hover:shadow-2xl transition duration-200">
                                        <div class="flex items-start mb-4">
                                            <div class="flex-shrink-0 w-14 h-14 bg-[#0297FE] rounded-full flex items-center justify-center mr-4">
                                                <span class="text-2xl">🇬🇧</span>
                                            </div>
                                            <div>
                                                <h3 class="text-2xl font-bold text-gray-800">United Kingdom</h3>
                                                <p class="text-[#0297FE] text-sm font-semibold">Business Office</p>
                                            </div>
                                        </div>
                                        <div class="bg-pink-50 rounded-lg p-4">
                                            <p class="font-bold text-pink-900 mb-2">Blu Lavelle LTD</p>
                                            <p class="text-gray-700 mb-2">
                                                <strong>Company Number:</strong> 16342615
                                            </p>
                                            <p class="text-gray-700">
                                                71-75 Shelton Street<br>
                                                Covent Garden, London<br>
                                                WC2H 9JQ, United Kingdom
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Vietnam Office -->
                                    <div class="bg-white rounded-lg p-6 shadow-xl border-2 border-red-200 hover:shadow-2xl transition duration-200">
                                        <div class="flex items-start mb-4">
                                            <div class="flex-shrink-0 w-14 h-14 bg-red-500 rounded-full flex items-center justify-center mr-4">
                                                <span class="text-2xl">🇻🇳</span>
                                            </div>
                                            <div>
                                                <h3 class="text-2xl font-bold text-gray-800">Vietnam</h3>
                                                <p class="text-red-600 text-sm font-semibold">Business Office</p>
                                            </div>
                                        </div>
                                        <div class="bg-red-50 rounded-lg p-4">
                                            <p class="font-bold text-red-900 mb-2">HM FULFILL COMPANY LIMITED</p>
                                            <p class="text-gray-700">
                                                63/9Đ Ap Chanh 1, Tan Xuan<br>
                                                Hoc Mon, Ho Chi Minh City<br>
                                                700000, Vietnam
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Hong Kong Office -->
                                    <div class="bg-white rounded-lg p-6 shadow-xl border-2 border-yellow-200 hover:shadow-2xl transition duration-200">
                                        <div class="flex items-start mb-4">
                                            <div class="flex-shrink-0 w-14 h-14 bg-yellow-500 rounded-full flex items-center justify-center mr-4">
                                                <span class="text-2xl">🇭🇰</span>
                                            </div>
                                            <div>
                                                <h3 class="text-2xl font-bold text-gray-800">Hong Kong</h3>
                                                <p class="text-yellow-600 text-sm font-semibold">Business Office</p>
                                            </div>
                                        </div>
                                        <div class="bg-yellow-50 rounded-lg p-4">
                                            <p class="font-bold text-yellow-900 mb-2">BLUE STAR TRADING LIMITED</p>
                                            <p class="text-gray-700">
                                                RM C, 6/F, WORLD TRUST TOWER<br>
                                                50 STANLEY STREET<br>
                                                CENTRAL, HONG KONG
                                            </p>
                                        </div>
                                    </div>

                                    <!-- US Office -->
                                    <div class="bg-white rounded-lg p-6 shadow-xl border-2 border-pink-200 hover:shadow-2xl transition duration-200">
                                        <div class="flex items-start mb-4">
                                            <div class="flex-shrink-0 w-14 h-14 bg-[#0297FE] rounded-full flex items-center justify-center mr-4">
                                                <span class="text-2xl">🇺🇸</span>
                                            </div>
                                            <div>
                                                <h3 class="text-2xl font-bold text-gray-800">United States</h3>
                                                <p class="text-[#0297FE] text-sm font-semibold">Business Office</p>
                                            </div>
                                        </div>
                                        <div class="bg-pink-50 rounded-lg p-4">
                                            <p class="font-bold text-pink-900 mb-2">Blu Lavelle LLC</p>
                                            <p class="text-gray-700">
                                                5900 BALCONES DR STE 100<br>
                                                AUSTIN, TX 78731<br>
                                                United States
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Warehouses -->
                            <div class="mb-8">
                                <div class="text-center mb-10">
                                    <h2 class="text-4xl font-bold text-gray-800 mb-3">Warehouse Locations</h2>
                                    <div class="flex justify-center">
                                        <div class="w-20 h-1 bg-gradient-to-r from-orange-500 to-red-500 rounded"></div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- US Warehouse -->
                                    <div class="bg-gradient-to-br from-orange-50 to-red-50 rounded-lg p-6 shadow-xl border-2 border-orange-300">
                                        <div class="flex items-start mb-4">
                                            <div class="flex-shrink-0 w-14 h-14 bg-gradient-to-br from-orange-500 to-red-500 rounded-full flex items-center justify-center mr-4 shadow-lg">
                                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="flex items-center mb-1">
                                                    <span class="text-2xl mr-2">🇺🇸</span>
                                                    <h3 class="text-2xl font-bold text-gray-800">US Warehouse</h3>
                                                </div>
                                                <p class="text-orange-600 text-sm font-semibold">Distribution Center</p>
                                            </div>
                                        </div>
                                        <div class="bg-white rounded-lg p-4 shadow-md">
                                            <p class="text-gray-700 leading-relaxed">
                                                1301 E ARAPAHO RD, STE 101<br>
                                                RICHARDSON, TX 75081<br>
                                                United States
                                            </p>
                                        </div>
                                    </div>

                                    <!-- UK Warehouse -->
                                    <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-lg p-6 shadow-xl border-2 border-pink-300">
                                        <div class="flex items-start mb-4">
                                            <div class="flex-shrink-0 w-14 h-14 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-full flex items-center justify-center mr-4 shadow-lg">
                                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="flex items-center mb-1">
                                                    <span class="text-2xl mr-2">🇬🇧</span>
                                                    <h3 class="text-2xl font-bold text-gray-800">UK Warehouse</h3>
                                                </div>
                                                <p class="text-[#0297FE] text-sm font-semibold">Distribution Center</p>
                                            </div>
                                        </div>
                                        <div class="bg-white rounded-lg p-4 shadow-md">
                                            <p class="text-gray-700 leading-relaxed mb-3">
                                                3 Kincraig Rd<br>
                                                Blackpool FY2 0FY<br>
                                                United Kingdom
                                            </p>
                                            <div class="border-t border-pink-200 pt-3">
                                                <p class="text-gray-700">
                                                    <strong>📞 Phone:</strong> <a href="tel:02045136359" class="text-[#0297FE] hover:text-[#d6386a] font-semibold">020 4513 6359</a>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Call to Action -->
                            <div class="bg-gradient-to-r from-[#0297FE] via-[#f89192] to-[#d6386a] rounded-lg p-10 text-center text-white">
                                <div class="flex justify-center mb-6">
                                    <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center backdrop-blur-sm">
                                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path>
                                        </svg>
                                    </div>
                                </div>
                                <h3 class="text-3xl font-bold mb-4">Ready to Connect?</h3>
                                <p class="text-xl text-green-100 mb-6 max-w-3xl mx-auto">
                                    We\'re here 24/7 to answer your questions and provide support. Choose your preferred method and get in touch today!
                                </p>
                                <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                                    <a href="mailto:admin@blulavelle.com" class="inline-flex items-center px-8 py-4 bg-white text-[#0297FE] font-bold rounded-lg shadow-xl hover:shadow-2xl transition duration-200 text-lg">
                                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        Email Us
                                    </a>
                                    <a href="https://wa.me/18563782798" target="_blank" class="inline-flex items-center px-8 py-4 bg-transparent border-2 border-white text-white font-bold rounded-lg hover:bg-white hover:text-[#0297FE] transition duration-200 text-lg">
                                        <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"></path>
                                        </svg>
                                        WhatsApp
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>',
                'excerpt' => 'Contact Blu Lavelle - Email, phone, WhatsApp, iMessage. Global offices in UK, US, Vietnam, Hong Kong with warehouse locations.',
                'status' => 'published',
                'published_at' => now(),
                'template' => 'default',
                'show_in_menu' => true,
                'menu_title' => 'Contact',
                'sort_order' => 8,
                'meta_title' => 'Contact Us - Blu Lavelle Global Support',
                'meta_description' => 'Contact Blu Lavelle through email, phone, WhatsApp, or iMessage. Find our global offices in UK, US, Vietnam, Hong Kong and warehouse locations.',
            ],
            [
                'user_id' => $admin->id,
                'title' => 'Frequently Asked Questions (FAQs)',
                'slug' => 'faqs',
                'content' => '<div class="max-w-6xl mx-auto py-8 px-4">
                    <div class="bg-white rounded-lg shadow-2xl overflow-hidden">
                        <!-- Hero Header -->
                        <div class="bg-gradient-to-r from-[#0297FE] via-[#f89192] to-[#d6386a] text-white px-8 py-16">
                            <div class="text-center">
                                <div class="flex justify-center mb-6">
                                    <div class="w-24 h-24 bg-white bg-opacity-20 rounded-full flex items-center justify-center backdrop-blur-sm">
                                        <svg class="w-14 h-14 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <h1 class="text-6xl font-bold mb-4">FAQs</h1>
                                <p class="text-2xl text-pink-100">Frequently Asked Questions</p>
                                <p class="text-lg text-pink-100 mt-4 max-w-3xl mx-auto">
                                    Find answers to common questions about ordering, shipping, returns, and more
                                </p>
                            </div>
                        </div>

                        <div class="px-8 py-12">
                            <!-- FAQ Items -->
                            <div class="space-y-6">
                                <!-- FAQ 1 -->
                                <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-lg border-2 border-pink-200 overflow-hidden">
                                    <div class="bg-gradient-to-r from-blue-500 to-cyan-500 px-6 py-4">
                                        <h3 class="text-2xl font-bold text-white flex items-center">
                                            <span class="w-10 h-10 bg-white text-[#0297FE] rounded-full flex items-center justify-center mr-3 font-bold">1</span>
                                            How do I place an order?
                                        </h3>
                                    </div>
                                    <div class="p-6">
                                        <p class="text-gray-700 mb-4">You can carry out the following steps to complete your order:</p>
                                        <div class="space-y-3">
                                            <div class="flex items-start bg-white rounded-lg p-4 shadow-sm">
                                                <span class="w-8 h-8 bg-[#0297FE] text-white rounded-full flex items-center justify-center mr-3 flex-shrink-0 font-bold">1</span>
                                                <p class="text-gray-800 pt-1">Choose your style on the product page</p>
                                            </div>
                                            <div class="flex items-start bg-white rounded-lg p-4 shadow-sm">
                                                <span class="w-8 h-8 bg-[#0297FE] text-white rounded-full flex items-center justify-center mr-3 flex-shrink-0 font-bold">2</span>
                                                <p class="text-gray-800 pt-1">Adjust the quantity of product</p>
                                            </div>
                                            <div class="flex items-start bg-white rounded-lg p-4 shadow-sm">
                                                <span class="w-8 h-8 bg-[#0297FE] text-white rounded-full flex items-center justify-center mr-3 flex-shrink-0 font-bold">3</span>
                                                <p class="text-gray-800 pt-1">Click the "Add To Cart" button</p>
                                            </div>
                                            <div class="flex items-start bg-white rounded-lg p-4 shadow-sm">
                                                <span class="w-8 h-8 bg-[#0297FE] text-white rounded-full flex items-center justify-center mr-3 flex-shrink-0 font-bold">4</span>
                                                <p class="text-gray-800 pt-1">Process payment and apply a discount code (if you have) to complete purchasing</p>
                                            </div>
                                            <div class="flex items-start bg-white rounded-lg p-4 shadow-sm">
                                                <span class="w-8 h-8 bg-[#0297FE] text-white rounded-full flex items-center justify-center mr-3 flex-shrink-0 font-bold">5</span>
                                                <p class="text-gray-800 pt-1">Receive your confirmation email/message when your order is successful</p>
                                            </div>
                                        </div>
                                        <div class="mt-4 p-4 bg-pink-100 rounded-lg">
                                            <p class="text-pink-900">If you need any further assistance, please contact us via email: <a href="mailto:support@blulavelle.com" class="font-semibold underline">support@blulavelle.com</a></p>
                                        </div>
                                    </div>
                                </div>

                                <!-- FAQ 2 -->
                                <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-lg border-2 border-pink-200 overflow-hidden">
                                    <div class="bg-gradient-to-r from-[#0297FE] to-[#d6386a] px-6 py-4">
                                        <h3 class="text-2xl font-bold text-white flex items-center">
                                            <span class="w-10 h-10 bg-white text-[#0297FE] rounded-full flex items-center justify-center mr-3 font-bold">2</span>
                                            Where does your order ship from?
                                        </h3>
                                    </div>
                                    <div class="p-6">
                                        <p class="text-gray-800 leading-relaxed mb-3">
                                            Orders are shipped with <strong>USPS</strong>, <strong>FedEx</strong>, or <strong>Canada Post</strong>. Most orders placed within the US will be shipped from the facilities in the US.
                                        </p>
                                        <p class="text-gray-800 leading-relaxed">
                                            For international orders, in Canada, Australia, Europe, and more to ensure you can get your order shipped from the facilities within your country (or the nearest facilities). Fulfillers strive to ensure you get your order as soon as possible in the highest quality.
                                        </p>
                                    </div>
                                </div>

                                <!-- FAQ 3 -->
                                <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-lg border-2 border-pink-200 overflow-hidden">
                                    <div class="bg-gradient-to-r from-[#0297FE] to-[#d6386a] px-6 py-4">
                                        <h3 class="text-2xl font-bold text-white flex items-center">
                                            <span class="w-10 h-10 bg-white text-[#0297FE] rounded-full flex items-center justify-center mr-3 font-bold">3</span>
                                            What is the shipping cost?
                                        </h3>
                                    </div>
                                    <div class="p-6">
                                        <p class="text-gray-800 leading-relaxed">
                                            Shipping times and costs can be varied based on the items you put on your virtual shopping bag. You can see the estimated shipping fees and times at the checkout.
                                        </p>
                                    </div>
                                </div>

                                <!-- FAQ 4 -->
                                <div class="bg-gradient-to-br from-orange-50 to-red-50 rounded-lg border-2 border-orange-200 overflow-hidden">
                                    <div class="bg-gradient-to-r from-orange-500 to-red-500 px-6 py-4">
                                        <h3 class="text-2xl font-bold text-white flex items-center">
                                            <span class="w-10 h-10 bg-white text-orange-600 rounded-full flex items-center justify-center mr-3 font-bold">4</span>
                                            How long will it take to ship my order?
                                        </h3>
                                    </div>
                                    <div class="p-6">
                                        <p class="text-gray-800 leading-relaxed">
                                            Please check shipping times and cost information at checkout or contact our support team for specific shipping estimates.
                                        </p>
                                    </div>
                                </div>

                                <!-- FAQ 5 -->
                                <div class="bg-gradient-to-br from-teal-50 to-cyan-50 rounded-lg border-2 border-pink-200 overflow-hidden">
                                    <div class="bg-gradient-to-r from-teal-500 to-cyan-500 px-6 py-4">
                                        <h3 class="text-2xl font-bold text-white flex items-center">
                                            <span class="w-10 h-10 bg-white text-[#0297FE] rounded-full flex items-center justify-center mr-3 font-bold">5</span>
                                            What is the status of my order?
                                        </h3>
                                    </div>
                                    <div class="p-6">
                                        <p class="text-gray-800 leading-relaxed">
                                            You can keep track of your order through your account dashboard. Log in to see real-time updates on your order status.
                                        </p>
                                    </div>
                                </div>

                                <!-- FAQ 6 -->
                                <div class="bg-gradient-to-br from-yellow-50 to-amber-50 rounded-lg border-2 border-yellow-200 overflow-hidden">
                                    <div class="bg-gradient-to-r from-yellow-500 to-amber-500 px-6 py-4">
                                        <h3 class="text-2xl font-bold text-white flex items-center">
                                            <span class="w-10 h-10 bg-white text-yellow-600 rounded-full flex items-center justify-center mr-3 font-bold">6</span>
                                            My orders are past the estimated delivery time
                                        </h3>
                                    </div>
                                    <div class="p-6">
                                        <p class="text-gray-800 leading-relaxed mb-3">
                                            Orders typically ship within <strong>1-5 days</strong> once you have submitted your order. Once your order has been shipped you can expect it to arrive within <strong>2-15 days</strong>. International orders may take an additional <strong>1-2 weeks</strong>.
                                        </p>
                                        <div class="bg-yellow-100 border-l-4 border-yellow-500 p-4 rounded">
                                            <p class="text-yellow-900">
                                                If your order has not arrived within the times stated above, please <a href="mailto:support@blulavelle.com" class="font-semibold underline">contact customer service</a>.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- FAQ 7 -->
                                <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-lg border-2 border-pink-200 overflow-hidden">
                                    <div class="bg-gradient-to-r from-[#0297FE] to-[#d6386a] px-6 py-4">
                                        <h3 class="text-2xl font-bold text-white flex items-center">
                                            <span class="w-10 h-10 bg-white text-[#0297FE] rounded-full flex items-center justify-center mr-3 font-bold">7</span>
                                            Why is my tracking information not working?
                                        </h3>
                                    </div>
                                    <div class="p-6">
                                        <p class="text-gray-800 leading-relaxed mb-3">
                                            Please note that tracking information updates once the order ships and has been picked up and scanned by the postal courier.
                                        </p>
                                        <div class="bg-indigo-100 border-l-4 border-[#0297FE] p-4 rounded">
                                            <p class="text-pink-900">
                                                If you placed your order over <strong>21 days ago</strong> and your tracking information is still not available, please <a href="mailto:support@blulavelle.com" class="font-semibold underline">contact customer support</a>. Be sure to have your order number and email that was used to make the purchase.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- FAQ 8 -->
                                <div class="bg-gradient-to-br from-pink-50 to-rose-50 rounded-lg border-2 border-pink-200 overflow-hidden">
                                    <div class="bg-gradient-to-r from-pink-500 to-rose-500 px-6 py-4">
                                        <h3 class="text-2xl font-bold text-white flex items-center">
                                            <span class="w-10 h-10 bg-white text-pink-600 rounded-full flex items-center justify-center mr-3 font-bold">8</span>
                                            Changes to order
                                        </h3>
                                    </div>
                                    <div class="p-6">
                                        <p class="text-gray-800 leading-relaxed">
                                            Order changes have to be made within <strong class="text-pink-600">4 hours</strong> of first placing the order. If your order is eligible, you can <a href="mailto:support@blulavelle.com" class="text-[#0297FE] hover:text-[#d6386a] font-semibold underline">request changes here</a>.
                                        </p>
                                    </div>
                                </div>

                                <!-- FAQ 9 -->
                                <div class="bg-gradient-to-br from-red-50 to-orange-50 rounded-lg border-2 border-red-200 overflow-hidden">
                                    <div class="bg-gradient-to-r from-red-500 to-orange-500 px-6 py-4">
                                        <h3 class="text-2xl font-bold text-white flex items-center">
                                            <span class="w-10 h-10 bg-white text-red-600 rounded-full flex items-center justify-center mr-3 font-bold">9</span>
                                            Order cancellation
                                        </h3>
                                    </div>
                                    <div class="p-6">
                                        <p class="text-gray-800 leading-relaxed">
                                            Order cancellations must be made within <strong class="text-red-600">4 hours</strong> after the order has been placed. If your order qualifies, you can <a href="mailto:support@blulavelle.com" class="text-[#0297FE] hover:text-[#d6386a] font-semibold underline">request cancellation here</a>.
                                        </p>
                                    </div>
                                </div>

                                <!-- FAQ 10 -->
                                <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-lg border-2 border-emerald-200 overflow-hidden">
                                    <div class="bg-gradient-to-r from-emerald-500 to-teal-500 px-6 py-4">
                                        <h3 class="text-2xl font-bold text-white flex items-center">
                                            <span class="w-10 h-10 bg-white text-emerald-600 rounded-full flex items-center justify-center mr-3 font-bold">10</span>
                                            Refund or Exchange
                                        </h3>
                                    </div>
                                    <div class="p-6">
                                        <p class="text-gray-800 leading-relaxed">
                                            If your item is missing, materially defective, or incorrect, please <a href="mailto:support@blulavelle.com" class="text-[#0297FE] hover:text-[#d6386a] font-semibold underline">contact us here</a>.
                                        </p>
                                    </div>
                                </div>

                                <!-- FAQ 11 -->
                                <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-lg border-2 border-violet-200 overflow-hidden">
                                    <div class="bg-gradient-to-r from-violet-500 to-purple-500 px-6 py-4">
                                        <h3 class="text-2xl font-bold text-white flex items-center">
                                            <span class="w-10 h-10 bg-white text-[#0297FE] rounded-full flex items-center justify-center mr-3 font-bold">11</span>
                                            Didn\'t Receive Confirmation Email
                                        </h3>
                                    </div>
                                    <div class="p-6">
                                        <p class="text-gray-800 leading-relaxed mb-3">
                                            When an order is placed, email is sent to you with your receipt. This email (the confirmation email) also contains your order details.
                                        </p>
                                        <p class="text-gray-700 mb-3 font-semibold">If you did not receive your confirmation email, please follow these steps:</p>
                                        <div class="space-y-2">
                                            <div class="flex items-start bg-white rounded-lg p-3 shadow-sm">
                                                <svg class="w-5 h-5 text-violet-500 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                                <p class="text-gray-800">Check your spam folder and other email accounts, especially if you checked out with PayPal</p>
                                            </div>
                                            <div class="flex items-start bg-white rounded-lg p-3 shadow-sm">
                                                <svg class="w-5 h-5 text-violet-500 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                                <p class="text-gray-800">If these don\'t work, please <a href="mailto:support@blulavelle.com" class="text-[#0297FE] hover:text-[#d6386a] font-semibold underline">contact us</a></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- FAQ 12 -->
                                <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-lg border-2 border-pink-200 overflow-hidden">
                                    <div class="bg-gradient-to-r from-[#0297FE] to-[#d6386a] px-6 py-4">
                                        <h3 class="text-2xl font-bold text-white flex items-center">
                                            <span class="w-10 h-10 bg-white text-[#0297FE] rounded-full flex items-center justify-center mr-3 font-bold">12</span>
                                            Size Guide
                                        </h3>
                                    </div>
                                    <div class="p-6">
                                        <p class="text-gray-800 leading-relaxed">
                                            Please check the sizing guide for all of the sizing information on different brands and products on each product page.
                                        </p>
                                    </div>
                                </div>

                                <!-- FAQ 13 -->
                                <div class="bg-gradient-to-br from-lime-50 to-green-50 rounded-lg border-2 border-lime-200 overflow-hidden">
                                    <div class="bg-gradient-to-r from-lime-500 to-green-500 px-6 py-4">
                                        <h3 class="text-2xl font-bold text-white flex items-center">
                                            <span class="w-10 h-10 bg-white text-lime-600 rounded-full flex items-center justify-center mr-3 font-bold">13</span>
                                            Will I be charged VAT taxes?
                                        </h3>
                                    </div>
                                    <div class="p-6">
                                        <p class="text-gray-800 leading-relaxed mb-3">
                                            Items shipping internationally from the US are shipped <strong>DDU (Delivered Duty Unpaid)</strong> and we do not collect VAT (Value Added Taxes). All taxes, duties, and customs fees are the responsibility of the recipient of the package.
                                        </p>
                                        <p class="text-gray-800 leading-relaxed mb-3">
                                            Depending on the receiving country, your package may incur local customs or VAT charges. We recommend contacting your local customs office for more information regarding your country\'s customs policies.
                                        </p>
                                        <div class="bg-lime-100 rounded-lg p-4">
                                            <p class="text-lime-900">
                                                <strong>Note:</strong> We do not charge any other taxes on the orders.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- FAQ 14 -->
                                <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-lg border-2 border-pink-200 overflow-hidden">
                                    <div class="bg-gradient-to-r from-blue-500 to-indigo-500 px-6 py-4">
                                        <h3 class="text-2xl font-bold text-white flex items-center">
                                            <span class="w-10 h-10 bg-white text-[#0297FE] rounded-full flex items-center justify-center mr-3 font-bold">14</span>
                                            How secure is my personal information?
                                        </h3>
                                    </div>
                                    <div class="p-6">
                                        <p class="text-gray-800 leading-relaxed mb-3">
                                            Blu Lavelle Store adheres to the <strong>highest industry standards</strong> to protect your personal information when you checkout and purchase from our online store.
                                        </p>
                                        <p class="text-gray-800 leading-relaxed mb-3">
                                            Your credit card information is encrypted during transmission using <strong>secure socket layer (SSL) technology</strong>, which is widely used on the Internet for processing payments. Your credit card information is only used to complete the requested transaction and is not subsequently stored.
                                        </p>
                                        <div class="bg-pink-100 border-l-4 border-[#0297FE] p-4 rounded">
                                            <p class="text-pink-900">
                                                If you need any further assistance, please contact us via email: <a href="mailto:support@blulavelle.com" class="font-semibold underline">support@blulavelle.com</a>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- FAQ 15 -->
                                <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-lg border-2 border-pink-200 overflow-hidden">
                                    <div class="bg-gradient-to-r from-[#0297FE] to-[#d6386a] px-6 py-4">
                                        <h3 class="text-2xl font-bold text-white flex items-center">
                                            <span class="w-10 h-10 bg-white text-[#0297FE] rounded-full flex items-center justify-center mr-3 font-bold">15</span>
                                            How do I contact customer support?
                                        </h3>
                                    </div>
                                    <div class="p-6">
                                        <p class="text-gray-800 leading-relaxed mb-4">
                                            We are glad to answer any questions that you may have. Please contact customer support:
                                        </p>
                                        <div class="flex flex-col sm:flex-row gap-3">
                                            <a href="mailto:support@blulavelle.com" class="inline-flex items-center justify-center px-6 py-3 bg-[#0297FE] hover:bg-purple-700 text-white font-semibold rounded-lg shadow-md transition duration-200">
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                </svg>
                                                support@blulavelle.com
                                            </a>
                                            <a href="/contact-us" class="inline-flex items-center justify-center px-6 py-3 bg-[#0297FE] hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-md transition duration-200">
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                Contact Page
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Call to Action Footer -->
                            <div class="mt-12 bg-gradient-to-r from-[#0297FE] via-[#f89192] to-[#d6386a] rounded-lg p-10 text-center text-white">
                                <div class="flex justify-center mb-6">
                                    <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center backdrop-blur-sm">
                                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <h3 class="text-3xl font-bold mb-4">Still Have Questions?</h3>
                                <p class="text-xl text-pink-100 mb-6 max-w-3xl mx-auto">
                                    Our customer support team is here to help you 24/7. Don\'t hesitate to reach out!
                                </p>
                                <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                                    <a href="mailto:support@blulavelle.com" class="inline-flex items-center px-8 py-4 bg-white text-[#0297FE] font-bold rounded-lg shadow-xl hover:shadow-2xl transition duration-200 text-lg">
                                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        Email Support
                                    </a>
                                    <a href="/contact-us" class="inline-flex items-center px-8 py-4 bg-transparent border-2 border-white text-white font-bold rounded-lg hover:bg-white hover:text-[#0297FE] transition duration-200 text-lg">
                                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Contact Us
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>',
                'excerpt' => 'Find answers to frequently asked questions about ordering, shipping, returns, refunds, tracking, and more on Blu Lavelle.',
                'status' => 'published',
                'published_at' => now(),
                'template' => 'default',
                'show_in_menu' => true,
                'menu_title' => 'FAQs',
                'sort_order' => 9,
                'meta_title' => 'FAQs - Frequently Asked Questions | Blu Lavelle Help Center',
                'meta_description' => 'Get answers to common questions about ordering, shipping, returns, tracking, payment, and more. Complete FAQ guide for Blu Lavelle customers.',
            ],
            [
                'user_id' => $admin->id,
                'title' => 'Local Support',
                'slug' => 'local-support',
                'content' => '<div class="max-w-5xl mx-auto py-8 px-4">
                    <div class="bg-white rounded-lg shadow-2xl overflow-hidden">
                        <!-- Hero Header -->
                        <div class="bg-gradient-to-r from-red-600 via-yellow-500 to-red-600 text-white px-8 py-16">
                            <div class="text-center">
                                <div class="flex justify-center mb-6">
                                    <div class="w-24 h-24 bg-white bg-opacity-20 rounded-full flex items-center justify-center backdrop-blur-sm">
                                        <span class="text-6xl">🇻🇳</span>
                                    </div>
                                </div>
                                <h1 class="text-6xl font-bold mb-4">Local Support</h1>
                                <p class="text-2xl text-red-100">Local Support - Vietnam</p>
                                <p class="text-lg text-yellow-100 mt-4 max-w-3xl mx-auto">
                                    Connect with our local team in Ho Chi Minh City for personalized assistance
                                </p>
                            </div>
                        </div>

                        <div class="px-8 py-12">
                            <!-- Introduction -->
                            <div class="bg-gradient-to-br from-red-50 to-yellow-50 border-l-4 border-red-500 rounded-r-lg p-6 mb-10 text-center">
                                <h2 class="text-3xl font-bold text-gray-800 mb-3">Contact Us</h2>
                                <p class="text-gray-700 text-lg">
                                    Reach out to our Vietnam support team for local assistance
                                </p>
                            </div>

                            <!-- Contact Methods Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
                                <!-- Email -->
                                <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-lg p-8 shadow-xl border-2 border-pink-300 hover:shadow-2xl transition duration-200">
                                    <div class="flex flex-col items-center text-center">
                                        <div class="w-20 h-20 bg-[#0297FE] rounded-full flex items-center justify-center mb-4 shadow-lg">
                                            <svg class="w-11 h-11 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                        <h3 class="text-2xl font-bold text-gray-800 mb-3">Email Support</h3>
                                        <a href="mailto:support@blulavelle.com" class="text-[#0297FE] hover:text-[#d6386a] font-bold text-xl underline break-all">support@blulavelle.com</a>
                                        <p class="text-gray-600 text-sm mt-2">Response within 24 hours</p>
                                    </div>
                                </div>

                                <!-- Phone -->
                                <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-lg p-8 shadow-xl border-2 border-pink-300 hover:shadow-2xl transition duration-200">
                                    <div class="flex flex-col items-center text-center">
                                        <div class="w-20 h-20 bg-[#0297FE] rounded-full flex items-center justify-center mb-4 shadow-lg">
                                            <svg class="w-11 h-11 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                            </svg>
                                        </div>
                                        <h3 class="text-2xl font-bold text-gray-800 mb-3">Call Us</h3>
                                        <a href="tel:+18563782798" class="text-[#0297FE] hover:text-pink-800 font-bold text-2xl">+1 856-378-2798</a>
                                        <p class="text-gray-600 text-sm mt-2">Available during business hours</p>
                                    </div>
                                </div>

                                <!-- iMessage -->
                                <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-lg p-8 shadow-xl border-2 border-pink-300 hover:shadow-2xl transition duration-200">
                                    <div class="flex flex-col items-center text-center">
                                        <div class="w-20 h-20 bg-[#0297FE] rounded-full flex items-center justify-center mb-4 shadow-lg">
                                            <svg class="w-11 h-11 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                                            </svg>
                                        </div>
                                        <h3 class="text-2xl font-bold text-gray-800 mb-3">iMessage</h3>
                                        <p class="text-[#0297FE] font-bold text-2xl">+1 856-378-2798</p>
                                        <p class="text-gray-600 text-sm mt-2">For iOS users</p>
                                    </div>
                                </div>

                                <!-- WhatsApp -->
                                <div class="bg-gradient-to-br from-green-50 to-lime-50 rounded-lg p-8 shadow-xl border-2 border-pink-300 hover:shadow-2xl transition duration-200">
                                    <div class="flex flex-col items-center text-center">
                                        <div class="w-20 h-20 bg-[#0297FE] rounded-full flex items-center justify-center mb-4 shadow-lg">
                                            <svg class="w-11 h-11 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"></path>
                                            </svg>
                                        </div>
                                        <h3 class="text-2xl font-bold text-gray-800 mb-3">WhatsApp</h3>
                                        <a href="https://wa.me/18563782798" target="_blank" class="text-[#0297FE] hover:text-pink-800 font-bold text-2xl">+1 856-378-2798</a>
                                        <p class="text-gray-600 text-sm mt-2">Quick messaging support</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Office Address -->
                            <div class="mb-8">
                                <div class="bg-gradient-to-r from-red-600 to-orange-600 text-white px-6 py-4 rounded-t-lg">
                                    <h2 class="text-3xl font-bold flex items-center justify-center">
                                        <svg class="w-9 h-9 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                        Office Address
                                    </h2>
                                </div>
                                <div class="bg-gradient-to-br from-red-50 to-orange-50 border-2 border-red-300 border-t-0 rounded-b-lg p-8">
                                    <div class="bg-white rounded-lg p-8 shadow-xl border-l-4 border-red-500">
                                        <div class="flex items-start mb-6">
                                            <div class="flex-shrink-0 w-16 h-16 bg-red-500 rounded-full flex items-center justify-center mr-4 shadow-lg">
                                                <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                </svg>
                                            </div>
                                            <div class="flex-1">
                                                <h3 class="text-2xl font-bold text-gray-800 mb-1 flex items-center">
                                                    <span class="text-3xl mr-2">🇻🇳</span>
                                                    Vietnam Local Office
                                                </h3>
                                                <p class="text-red-600 font-semibold">Ho Chi Minh City</p>
                                            </div>
                                        </div>
                                        
                                        <div class="bg-gradient-to-r from-red-50 to-yellow-50 rounded-lg p-6 border border-red-200">
                                            <div class="text-center md:text-left">
                                                <p class="text-xl text-gray-800 leading-relaxed mb-2">
                                                    <strong class="text-red-700">📍 Address:</strong>
                                                </p>
                                                <p class="text-lg text-gray-700 leading-relaxed">
                                                    24 Thanh Xuan 14 Street<br>
                                                    Thanh Xuan Ward, District 12<br>
                                                    Ho Chi Minh City, Vietnam
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Access Links -->
                            <div class="bg-gradient-to-br from-pink-50 to-pink-100 border-2 border-pink-300 rounded-lg p-8 mb-8">
                                <h3 class="text-2xl font-bold text-pink-900 mb-6 text-center flex items-center justify-center">
                                    <svg class="w-8 h-8 mr-3 text-[#0297FE]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                    Quick Access
                                </h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <a href="mailto:support@blulavelle.com" class="flex items-center bg-white hover:bg-pink-50 rounded-lg p-5 shadow-md border-l-4 border-[#0297FE] transition duration-200">
                                        <div class="flex-shrink-0 w-12 h-12 bg-[#0297FE] rounded-full flex items-center justify-center mr-4">
                                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-800 text-lg">Send Email</h4>
                                            <p class="text-gray-600 text-sm">Email us</p>
                                        </div>
                                    </a>

                                    <a href="tel:+18563782798" class="flex items-center bg-white hover:bg-pink-50 rounded-lg p-5 shadow-md border-l-4 border-[#0297FE] transition duration-200">
                                        <div class="flex-shrink-0 w-12 h-12 bg-[#0297FE] rounded-full flex items-center justify-center mr-4">
                                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-800 text-lg">Call Now</h4>
                                            <p class="text-gray-600 text-sm">Call now</p>
                                        </div>
                                    </a>

                                    <a href="sms:+18563782798" class="flex items-center bg-white hover:bg-pink-50 rounded-lg p-5 shadow-md border-l-4 border-purple-500 transition duration-200">
                                        <div class="flex-shrink-0 w-12 h-12 bg-[#0297FE] rounded-full flex items-center justify-center mr-4">
                                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-800 text-lg">iMessage</h4>
                                            <p class="text-gray-600 text-sm">Message via iMessage</p>
                                        </div>
                                    </a>

                                    <a href="https://wa.me/18563782798" target="_blank" class="flex items-center bg-white hover:bg-pink-50 rounded-lg p-5 shadow-md border-l-4 border-green-600 transition duration-200">
                                        <div class="flex-shrink-0 w-12 h-12 bg-[#0297FE] rounded-full flex items-center justify-center mr-4">
                                            <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-800 text-lg">WhatsApp</h4>
                                            <p class="text-gray-600 text-sm">Chat via WhatsApp</p>
                                        </div>
                                    </a>
                                </div>
                            </div>

                            <!-- Why Choose Local Support -->
                            <div class="bg-gradient-to-br from-yellow-50 to-amber-50 border-l-4 border-yellow-500 rounded-r-lg p-6 mb-8">
                                <h3 class="text-2xl font-bold text-yellow-900 mb-4 flex items-center">
                                    <svg class="w-8 h-8 mr-3 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Why Choose Local Support?
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-start">
                                            <svg class="w-6 h-6 text-yellow-500 mr-2 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <p class="text-gray-800"><strong>Vietnamese Language Support</strong></p>
                                        </div>
                                    </div>
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-start">
                                            <svg class="w-6 h-6 text-yellow-500 mr-2 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <p class="text-gray-800"><strong>Local Business Hours</strong></p>
                                        </div>
                                    </div>
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-start">
                                            <svg class="w-6 h-6 text-yellow-500 mr-2 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <p class="text-gray-800"><strong>Faster Response Time</strong></p>
                                        </div>
                                    </div>
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-start">
                                            <svg class="w-6 h-6 text-yellow-500 mr-2 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <p class="text-gray-800"><strong>Local Understanding</strong></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Call to Action Footer -->
                            <div class="bg-gradient-to-r from-red-600 via-yellow-500 to-red-600 rounded-lg p-10 text-center text-white">
                                <div class="flex justify-center mb-6">
                                    <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center backdrop-blur-sm">
                                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <h3 class="text-3xl font-bold mb-4">Ready to Support You!</h3>
                                <p class="text-xl text-yellow-100 mb-6 max-w-3xl mx-auto">
                                    Our local team in Vietnam is ready to assist you in your preferred language. Contact us today!
                                </p>
                                <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                                    <a href="mailto:support@blulavelle.com" class="inline-flex items-center px-8 py-4 bg-white text-red-600 font-bold rounded-lg shadow-xl hover:shadow-2xl transition duration-200 text-lg">
                                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        Email Us
                                    </a>
                                    <a href="https://wa.me/18563782798" target="_blank" class="inline-flex items-center px-8 py-4 bg-transparent border-2 border-white text-white font-bold rounded-lg hover:bg-white hover:text-red-600 transition duration-200 text-lg">
                                        <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"></path>
                                        </svg>
                                        WhatsApp
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>',
                'excerpt' => 'Local support in Vietnam - Contact our Ho Chi Minh City office via email, phone, iMessage, or WhatsApp for Vietnamese language assistance.',
                'status' => 'published',
                'published_at' => now(),
                'template' => 'default',
                'show_in_menu' => true,
                'menu_title' => 'Local Support',
                'sort_order' => 10,
                'meta_title' => 'Local Support Vietnam - Blu Lavelle Local Support',
                'meta_description' => 'Contact Blu Lavelle local support team in Ho Chi Minh City, Vietnam. Vietnamese language support via email, phone, iMessage, and WhatsApp.',
            ],
            [
                'user_id' => $admin->id,
                'title' => 'Free Return',
                'slug' => 'free-return',
                'content' => '<div class="max-w-6xl mx-auto py-8 px-4">
                    <div class="bg-white rounded-lg shadow-2xl overflow-hidden">
                        <!-- Hero Header -->
                        <div class="relative bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 text-white px-8 py-16">
                            <div class="absolute inset-0 bg-black opacity-10"></div>
                            <div class="relative z-10 text-center">
                                <div class="flex justify-center mb-6">
                                    <div class="w-32 h-32 bg-white bg-opacity-20 rounded-full flex items-center justify-center backdrop-blur-sm shadow-2xl">
                                        <svg class="w-20 h-20 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                        </svg>
                                    </div>
                                </div>
                                <h1 class="text-6xl font-bold mb-4">Free Return</h1>
                                <p class="text-3xl text-green-100 mb-6">Returns & Exchanges Policy</p>
                                <div class="flex justify-center mb-4">
                                    <div class="w-24 h-1 bg-white rounded"></div>
                                </div>
                                <p class="text-xl text-pink-100 max-w-4xl mx-auto leading-relaxed">
                                    Your satisfaction is our priority - Simple and straightforward returns within 30 days
                                </p>
                            </div>
                        </div>

                        <div class="px-8 py-12">
                            <!-- Introduction -->
                            <div class="bg-gradient-to-br from-pink-50 to-pink-100 border-l-4 border-[#0297FE] rounded-r-lg p-8 mb-10">
                                <p class="text-xl text-gray-800 leading-relaxed mb-4">
                                    At <strong class="text-[#0297FE]">Blu Lavelle</strong>, we strive to ensure your satisfaction with every purchase. If you need to return or exchange an item, our policy makes the process simple and straightforward.
                                </p>
                                <div class="bg-white rounded-lg p-6 shadow-md">
                                    <p class="text-gray-800 leading-relaxed">
                                        If you need assistance, please contact us at <a href="mailto:support@blulavelle.com" class="text-[#0297FE] hover:text-[#d6386a] font-semibold underline">support@blulavelle.com</a>
                                    </p>
                                </div>
                            </div>

                            <!-- No Restocking Fee -->
                            <div class="bg-gradient-to-r from-pink-100 to-pink-200 border-2 border-[#0297FE] rounded-lg p-8 mb-10">
                                <div class="flex items-center justify-center">
                                    <div class="text-center">
                                        <div class="flex justify-center mb-4">
                                            <div class="w-24 h-24 bg-[#0297FE] rounded-full flex items-center justify-center shadow-xl">
                                                <svg class="w-14 h-14 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <h2 class="text-4xl font-bold text-pink-900 mb-3">Restocking Fee</h2>
                                        <p class="text-6xl font-bold text-[#0297FE] mb-2">NO FEE</p>
                                        <p class="text-gray-700 text-lg">We do not charge any restocking fees for returns or exchanges</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Eligible Reasons Section -->
                            <div class="mb-10">
                                <div class="bg-gradient-to-r from-[#0297FE] to-[#d6386a] text-white px-6 py-5 rounded-t-lg">
                                    <h2 class="text-4xl font-bold text-center">Eligible Reasons for Return</h2>
                                    <p class="text-center text-pink-100 mt-2 text-lg">Returns and exchanges are only permitted under the following conditions:</p>
                                </div>

                                <div class="space-y-6 mt-6">
                                    <!-- Reason A -->
                                    <div class="bg-gradient-to-br from-red-50 to-rose-50 border-2 border-red-300 rounded-lg overflow-hidden">
                                        <div class="bg-gradient-to-r from-red-500 to-rose-500 px-6 py-4">
                                            <div class="flex items-center">
                                                <span class="w-12 h-12 bg-white text-red-600 rounded-full flex items-center justify-center mr-3 text-2xl font-bold shadow-lg">A</span>
                                                <h3 class="text-2xl font-bold text-white">Wrong, Damaged, or Faulty Items</h3>
                                            </div>
                                        </div>
                                        <div class="p-6">
                                            <p class="text-gray-800 font-semibold mb-4 text-lg">We will fully support returns or exchanges for items that:</p>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div class="bg-white rounded-lg p-4 shadow-sm border-l-4 border-red-500">
                                                    <div class="flex items-start">
                                                        <svg class="w-6 h-6 text-red-500 mr-3 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                        <p class="text-gray-800">Do not match the product description (wrong item, incorrect material, wrong size)</p>
                                                    </div>
                                                </div>
                                                <div class="bg-white rounded-lg p-4 shadow-sm border-l-4 border-red-500">
                                                    <div class="flex items-start">
                                                        <svg class="w-6 h-6 text-red-500 mr-3 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                        <p class="text-gray-800">Are received torn, dirty, wet, or with defective/hairy fabric</p>
                                                    </div>
                                                </div>
                                                <div class="bg-white rounded-lg p-4 shadow-sm border-l-4 border-red-500">
                                                    <div class="flex items-start">
                                                        <svg class="w-6 h-6 text-red-500 mr-3 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                        <p class="text-gray-800">Have visible print defects, such as blurring or incorrect placement</p>
                                                    </div>
                                                </div>
                                                <div class="bg-white rounded-lg p-4 shadow-sm border-l-4 border-red-500">
                                                    <div class="flex items-start">
                                                        <svg class="w-6 h-6 text-red-500 mr-3 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                        <p class="text-gray-800">Show damage, such as peeling prints, after the first wash</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Reason B -->
                                    <div class="bg-gradient-to-br from-orange-50 to-amber-50 border-2 border-orange-300 rounded-lg overflow-hidden">
                                        <div class="bg-gradient-to-r from-orange-500 to-amber-500 px-6 py-4">
                                            <div class="flex items-center">
                                                <span class="w-12 h-12 bg-white text-orange-600 rounded-full flex items-center justify-center mr-3 text-2xl font-bold shadow-lg">B</span>
                                                <h3 class="text-2xl font-bold text-white">Incorrect Size</h3>
                                            </div>
                                        </div>
                                        <div class="p-6">
                                            <div class="bg-white rounded-lg p-5 shadow-md border border-orange-200">
                                                <p class="text-gray-800 leading-relaxed">
                                                    If the product you received does not match the size guide <strong class="text-orange-600">(a discrepancy of more than 1.5")</strong> from standard measurements, we will assist you with the return or exchange.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Reason C -->
                                    <div class="bg-gradient-to-br from-yellow-50 to-amber-50 border-2 border-yellow-300 rounded-lg overflow-hidden">
                                        <div class="bg-gradient-to-r from-yellow-500 to-amber-500 px-6 py-4">
                                            <div class="flex items-center">
                                                <span class="w-12 h-12 bg-white text-yellow-600 rounded-full flex items-center justify-center mr-3 text-2xl font-bold shadow-lg">C</span>
                                                <h3 class="text-2xl font-bold text-white">Non-Fitting Items</h3>
                                            </div>
                                        </div>
                                        <div class="p-6">
                                            <div class="bg-white rounded-lg p-5 shadow-md border border-yellow-200">
                                                <p class="text-gray-800 leading-relaxed mb-2">
                                                    Returns or exchanges are accepted for non-fitting items.
                                                </p>
                                                <div class="bg-yellow-100 border border-yellow-300 rounded-lg p-3">
                                                    <p class="text-yellow-900 font-semibold">
                                                        <strong>⚠️ Important:</strong> For <strong>press-on nail sets only</strong>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Reason D -->
                                    <div class="bg-gradient-to-br from-pink-50 to-pink-100 border-2 border-pink-300 rounded-lg overflow-hidden">
                                        <div class="bg-gradient-to-r from-blue-500 to-cyan-500 px-6 py-4">
                                            <div class="flex items-center">
                                                <span class="w-12 h-12 bg-white text-[#0297FE] rounded-full flex items-center justify-center mr-3 text-2xl font-bold shadow-lg">D</span>
                                                <h3 class="text-2xl font-bold text-white">Damage Caused During Shipping</h3>
                                            </div>
                                        </div>
                                        <div class="p-6">
                                            <p class="text-gray-800 leading-relaxed mb-4">
                                                If your package arrives damaged or contains the wrong items due to shipping issues, please:
                                            </p>
                                            <div class="space-y-3">
                                                <div class="flex items-start bg-white rounded-lg p-4 shadow-sm">
                                                    <svg class="w-6 h-6 text-blue-500 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <p class="text-gray-800">Check the package carefully upon delivery</p>
                                                </div>
                                                <div class="flex items-start bg-white rounded-lg p-4 shadow-sm">
                                                    <svg class="w-6 h-6 text-blue-500 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <p class="text-gray-800">Immediately return defective items to the courier, or</p>
                                                </div>
                                                <div class="flex items-start bg-white rounded-lg p-4 shadow-sm">
                                                    <svg class="w-6 h-6 text-blue-500 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <p class="text-gray-800">Contact us within <strong>30 days</strong> for prompt support</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Eligibility Criteria -->
                            <div class="mb-10">
                                <div class="bg-gradient-to-r from-[#0297FE] to-[#d6386a] text-white px-6 py-5 rounded-t-lg">
                                    <h2 class="text-4xl font-bold text-center flex items-center justify-center">
                                        <svg class="w-9 h-9 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Eligibility for Return or Exchange
                                    </h2>
                                </div>
                                <div class="bg-pink-50 border-2 border-pink-300 border-t-0 rounded-b-lg p-8">
                                    <p class="text-gray-800 font-semibold mb-6 text-lg text-center">To qualify for a return or exchange, the following conditions must be met:</p>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                        <div class="bg-white rounded-lg p-6 shadow-lg border-2 border-pink-200">
                                            <div class="flex justify-center mb-4">
                                                <div class="w-14 h-14 bg-[#0297FE] rounded-full flex items-center justify-center">
                                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <p class="text-gray-800 text-center">The product falls under one of the <strong>eligible reasons</strong> listed above</p>
                                        </div>
                                        
                                        <div class="bg-white rounded-lg p-6 shadow-lg border-2 border-pink-200">
                                            <div class="flex justify-center mb-4">
                                                <div class="w-14 h-14 bg-[#0297FE] rounded-full flex items-center justify-center">
                                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <p class="text-gray-800 text-center">The item shows <strong>no signs of use</strong>, retains neck label, and is in original packaging</p>
                                        </div>
                                        
                                        <div class="bg-white rounded-lg p-6 shadow-lg border-2 border-pink-200">
                                            <div class="flex justify-center mb-4">
                                                <div class="w-14 h-14 bg-[#0297FE] rounded-full flex items-center justify-center">
                                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <p class="text-gray-800 text-center">Request submitted within <strong>30 days</strong> from delivery date</p>
                                        </div>
                                    </div>

                                    <div class="mt-6 bg-pink-100 border-l-4 border-[#0297FE] rounded-r-lg p-5">
                                        <p class="text-pink-900 font-semibold text-lg">
                                            🌍 For orders shipped outside the US: Defective or unwanted items are supported within <strong>60 days</strong> of delivery
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Return Process -->
                            <div class="mb-10">
                                <div class="bg-gradient-to-r from-[#0297FE] to-[#d6386a] text-white px-6 py-5 rounded-t-lg">
                                    <h2 class="text-4xl font-bold text-center flex items-center justify-center">
                                        <svg class="w-9 h-9 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                        </svg>
                                        Return Process
                                    </h2>
                                    <p class="text-center text-pink-100 mt-2 text-lg">Follow these simple steps for a smooth return experience</p>
                                </div>
                                <div class="bg-pink-50 border-2 border-pink-300 border-t-0 rounded-b-lg p-8">
                                    <div class="space-y-6">
                                        <!-- Step 1 -->
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 w-20 h-20 bg-gradient-to-br from-[#0297FE] to-[#d6386a] text-white rounded-full flex items-center justify-center mr-5 text-3xl font-bold shadow-xl">1</div>
                                            <div class="flex-1 bg-white rounded-lg p-6 shadow-lg border-2 border-pink-200">
                                                <h3 class="text-2xl font-bold text-pink-900 mb-3">Verify Eligibility</h3>
                                                <p class="text-gray-800 leading-relaxed">
                                                    Before initiating a return, ensure your item meets the eligibility criteria outlined above.
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Step 2 -->
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 w-20 h-20 bg-gradient-to-br from-[#0297FE] to-[#d6386a] text-white rounded-full flex items-center justify-center mr-5 text-3xl font-bold shadow-xl">2</div>
                                            <div class="flex-1 bg-white rounded-lg p-6 shadow-lg border-2 border-pink-200">
                                                <h3 class="text-2xl font-bold text-pink-900 mb-3">Contact Us</h3>
                                                <p class="text-gray-800 mb-4">Reach out via email at <a href="mailto:support@blulavelle.com" class="text-[#0297FE] hover:text-[#d6386a] font-semibold underline">support@blulavelle.com</a></p>
                                                <p class="text-gray-700 font-semibold mb-3">Provide the following information:</p>
                                                <div class="space-y-2">
                                                    <div class="flex items-start bg-pink-50 rounded-lg p-3">
                                                        <svg class="w-5 h-5 text-[#0297FE] mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                        <p class="text-gray-800"><strong>Order Details:</strong> Include your order number and relevant information</p>
                                                    </div>
                                                    <div class="flex items-start bg-pink-50 rounded-lg p-3">
                                                        <svg class="w-5 h-5 text-[#0297FE] mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                        <p class="text-gray-800"><strong>Photos of Packaging Label:</strong> Clearly show the shipping label</p>
                                                    </div>
                                                    <div class="flex items-start bg-pink-50 rounded-lg p-3">
                                                        <svg class="w-5 h-5 text-[#0297FE] mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                        <p class="text-gray-800"><strong>Photos of the Issue:</strong> Highlight damaged, faulty, or incorrect aspects</p>
                                                    </div>
                                                    <div class="flex items-start bg-pink-50 rounded-lg p-3">
                                                        <svg class="w-5 h-5 text-[#0297FE] mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                        <p class="text-gray-800">For wrong-sized items: Include photos of actual measurements (width and length)</p>
                                                    </div>
                                                    <div class="flex items-start bg-pink-50 rounded-lg p-3">
                                                        <svg class="w-5 h-5 text-[#0297FE] mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                        <p class="text-gray-800"><strong>Replacement Details:</strong> Specify the item you wish to receive</p>
                                                    </div>
                                                </div>
                                                <div class="mt-4 bg-pink-100 border border-pink-300 rounded-lg p-3">
                                                    <p class="text-pink-900 text-sm">
                                                        <strong>📸 Note:</strong> For multiple items, provide photos/videos of all items placed side by side on a flat surface
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Step 3 -->
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 w-20 h-20 bg-gradient-to-br from-pink-500 to-rose-500 text-white rounded-full flex items-center justify-center mr-5 text-3xl font-bold shadow-xl">3</div>
                                            <div class="flex-1 bg-white rounded-lg p-6 shadow-lg border-2 border-pink-200">
                                                <h3 class="text-2xl font-bold text-pink-900 mb-3">Verification & Resolution</h3>
                                                <p class="text-gray-800 mb-4 leading-relaxed">
                                                    Once we verify your claim, we will issue a refund or resend the replacement to your address within <strong class="text-pink-600">7 business days</strong>.
                                                </p>
                                                <div class="bg-pink-100 border-2 border-[#0297FE] rounded-lg p-5">
                                                    <div class="space-y-3">
                                                        <p class="text-pink-900 font-semibold text-lg flex items-center">
                                                            <svg class="w-6 h-6 mr-2 text-[#0297FE]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                            </svg>
                                                            You do NOT need to return the original package unless instructed
                                                        </p>
                                                        <p class="text-pink-900 font-semibold text-lg flex items-center">
                                                            <svg class="w-6 h-6 mr-2 text-[#0297FE]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                            </svg>
                                                            Replacements will be of equal or higher value
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Important Notes -->
                            <div class="bg-gradient-to-br from-amber-100 to-orange-100 border-2 border-amber-500 rounded-lg p-8 mb-10">
                                <h3 class="text-3xl font-bold text-amber-900 mb-6 flex items-center justify-center">
                                    <svg class="w-9 h-9 mr-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    Important Notes
                                </h3>
                                <div class="space-y-4">
                                    <div class="bg-white rounded-lg p-5 shadow-md border-l-4 border-amber-500">
                                        <p class="text-gray-800 leading-relaxed">
                                            <strong class="text-amber-800">⚠️</strong> If the replacement costs more, you will need to pay the difference
                                        </p>
                                    </div>
                                    <div class="bg-white rounded-lg p-5 shadow-md border-l-4 border-red-500">
                                        <p class="text-gray-800 leading-relaxed">
                                            <strong class="text-red-800">⚠️</strong> Products returned <strong>without verification are ineligible</strong> for support
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Call to Action Footer -->
                            <div class="bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 rounded-lg p-10 text-center text-white">
                                <div class="flex justify-center mb-6">
                                    <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center backdrop-blur-sm">
                                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <h3 class="text-3xl font-bold mb-4">Need Help with a Return?</h3>
                                <p class="text-xl text-green-100 mb-6 max-w-3xl mx-auto">
                                    If you have any questions or need assistance with your return or exchange, our support team is here to help!
                                </p>
                                <a href="mailto:support@blulavelle.com" class="inline-flex items-center px-10 py-4 bg-white text-[#0297FE] font-bold rounded-lg shadow-xl hover:shadow-2xl transition duration-200 text-xl">
                                    <svg class="w-7 h-7 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    Contact Support Team
                                </a>
                            </div>
                        </div>
                    </div>
                </div>',
                'excerpt' => 'Free returns within 30 days - No restocking fee. Easy return process for wrong, damaged, incorrect size, or non-fitting items.',
                'status' => 'published',
                'published_at' => now(),
                'template' => 'default',
                'show_in_menu' => true,
                'menu_title' => 'Free Return',
                'sort_order' => 11,
                'meta_title' => 'Free Return - Blu Lavelle Easy Returns & Exchanges',
                'meta_description' => 'Free returns and exchanges within 30 days. No restocking fee. Simple return process for wrong, damaged, or non-fitting items on Blu Lavelle.',
            ],
            [
                'user_id' => $admin->id,
                'title' => 'Contact Us',
                'slug' => 'contact-us',
                'content' => '<div class="max-w-7xl mx-auto py-8 px-4">
                    <div class="bg-white rounded-lg shadow-2xl overflow-hidden">
                        <!-- Hero Header -->
                        <div class="bg-gradient-to-r from-[#0297FE] via-[#f89192] to-[#d6386a] text-white px-8 py-16">
                            <div class="text-center">
                                <div class="flex justify-center mb-6">
                                    <div class="w-24 h-24 bg-white bg-opacity-20 rounded-full flex items-center justify-center backdrop-blur-sm">
                                        <svg class="w-14 h-14 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <h1 class="text-6xl font-bold mb-4">Contact Us</h1>
                                <p class="text-2xl text-green-100">We\'re Here to Help!</p>
                                <p class="text-lg text-teal-100 mt-4 max-w-3xl mx-auto">
                                    Get in touch with us through any of the channels below. We\'re available worldwide to support your needs.
                                </p>
                            </div>
                        </div>

                        <div class="px-8 py-12">
                            <!-- Contact Methods -->
                            <div class="mb-16">
                                <div class="text-center mb-10">
                                    <h2 class="text-4xl font-bold text-gray-800 mb-3">Get In Touch</h2>
                                    <div class="flex justify-center">
                                        <div class="w-20 h-1 bg-gradient-to-r from-[#0297FE] to-[#d6386a] rounded"></div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                                    <!-- Email -->
                                    <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-lg p-6 shadow-lg border-2 border-pink-200 hover:border-blue-400 transition duration-200">
                                        <div class="flex justify-center mb-4">
                                            <div class="w-16 h-16 bg-[#0297FE] rounded-full flex items-center justify-center shadow-lg">
                                                <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <h3 class="text-xl font-bold text-center text-gray-800 mb-2">Email</h3>
                                        <p class="text-center">
                                            <a href="mailto:admin@blulavelle.com" class="text-[#0297FE] hover:text-[#d6386a] font-semibold text-lg underline break-all">admin@blulavelle.com</a>
                                        </p>
                                    </div>

                                    <!-- Phone -->
                                    <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-lg p-6 shadow-lg border-2 border-pink-200 hover:border-[#0297FE] transition duration-200">
                                        <div class="flex justify-center mb-4">
                                            <div class="w-16 h-16 bg-[#0297FE] rounded-full flex items-center justify-center shadow-lg">
                                                <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <h3 class="text-xl font-bold text-center text-gray-800 mb-2">Call Us</h3>
                                        <p class="text-center">
                                            <a href="tel:+18563782798" class="text-[#0297FE] hover:text-pink-800 font-semibold text-lg">+1 856-378-2798</a>
                                        </p>
                                    </div>

                                    <!-- iMessage -->
                                    <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-lg p-6 shadow-lg border-2 border-pink-200 hover:border-purple-400 transition duration-200">
                                        <div class="flex justify-center mb-4">
                                            <div class="w-16 h-16 bg-[#0297FE] rounded-full flex items-center justify-center shadow-lg">
                                                <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <h3 class="text-xl font-bold text-center text-gray-800 mb-2">iMessage</h3>
                                        <p class="text-center text-[#0297FE] font-semibold text-lg">+1 856-378-2798</p>
                                    </div>

                                    <!-- WhatsApp -->
                                    <div class="bg-gradient-to-br from-green-50 to-lime-50 rounded-lg p-6 shadow-lg border-2 border-pink-200 hover:border-[#0297FE] transition duration-200">
                                        <div class="flex justify-center mb-4">
                                            <div class="w-16 h-16 bg-[#0297FE] rounded-full flex items-center justify-center shadow-lg">
                                                <svg class="w-9 h-9 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <h3 class="text-xl font-bold text-center text-gray-800 mb-2">WhatsApp</h3>
                                        <p class="text-center">
                                            <a href="https://wa.me/18563782798" target="_blank" class="text-[#0297FE] hover:text-pink-800 font-semibold text-lg">+1 856-378-2798</a>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Business Addresses -->
                            <div class="mb-16">
                                <div class="text-center mb-10">
                                    <h2 class="text-4xl font-bold text-gray-800 mb-3">Our Global Offices</h2>
                                    <div class="flex justify-center">
                                        <div class="w-20 h-1 bg-gradient-to-r from-[#0297FE] to-[#d6386a] rounded"></div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- UK Office -->
                                    <div class="bg-white rounded-lg p-6 shadow-xl border-2 border-pink-200 hover:shadow-2xl transition duration-200">
                                        <div class="flex items-start mb-4">
                                            <div class="flex-shrink-0 w-14 h-14 bg-[#0297FE] rounded-full flex items-center justify-center mr-4">
                                                <span class="text-2xl">🇬🇧</span>
                                            </div>
                                            <div>
                                                <h3 class="text-2xl font-bold text-gray-800">United Kingdom</h3>
                                                <p class="text-[#0297FE] text-sm font-semibold">Business Office</p>
                                            </div>
                                        </div>
                                        <div class="bg-pink-50 rounded-lg p-4">
                                            <p class="font-bold text-pink-900 mb-2">Blu Lavelle LTD</p>
                                            <p class="text-gray-700 mb-2">
                                                <strong>Company Number:</strong> 16342615
                                            </p>
                                            <p class="text-gray-700">
                                                71-75 Shelton Street<br>
                                                Covent Garden, London<br>
                                                WC2H 9JQ, United Kingdom
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Vietnam Office -->
                                    <div class="bg-white rounded-lg p-6 shadow-xl border-2 border-red-200 hover:shadow-2xl transition duration-200">
                                        <div class="flex items-start mb-4">
                                            <div class="flex-shrink-0 w-14 h-14 bg-red-500 rounded-full flex items-center justify-center mr-4">
                                                <span class="text-2xl">🇻🇳</span>
                                            </div>
                                            <div>
                                                <h3 class="text-2xl font-bold text-gray-800">Vietnam</h3>
                                                <p class="text-red-600 text-sm font-semibold">Business Office</p>
                                            </div>
                                        </div>
                                        <div class="bg-red-50 rounded-lg p-4">
                                            <p class="font-bold text-red-900 mb-2">HM FULFILL COMPANY LIMITED</p>
                                            <p class="text-gray-700">
                                                63/9Đ Ap Chanh 1, Tan Xuan<br>
                                                Hoc Mon, Ho Chi Minh City<br>
                                                700000, Vietnam
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Hong Kong Office -->
                                    <div class="bg-white rounded-lg p-6 shadow-xl border-2 border-yellow-200 hover:shadow-2xl transition duration-200">
                                        <div class="flex items-start mb-4">
                                            <div class="flex-shrink-0 w-14 h-14 bg-yellow-500 rounded-full flex items-center justify-center mr-4">
                                                <span class="text-2xl">🇭🇰</span>
                                            </div>
                                            <div>
                                                <h3 class="text-2xl font-bold text-gray-800">Hong Kong</h3>
                                                <p class="text-yellow-600 text-sm font-semibold">Business Office</p>
                                            </div>
                                        </div>
                                        <div class="bg-yellow-50 rounded-lg p-4">
                                            <p class="font-bold text-yellow-900 mb-2">BLUE STAR TRADING LIMITED</p>
                                            <p class="text-gray-700">
                                                RM C, 6/F, WORLD TRUST TOWER<br>
                                                50 STANLEY STREET<br>
                                                CENTRAL, HONG KONG
                                            </p>
                                        </div>
                                    </div>

                                    <!-- US Office -->
                                    <div class="bg-white rounded-lg p-6 shadow-xl border-2 border-pink-200 hover:shadow-2xl transition duration-200">
                                        <div class="flex items-start mb-4">
                                            <div class="flex-shrink-0 w-14 h-14 bg-[#0297FE] rounded-full flex items-center justify-center mr-4">
                                                <span class="text-2xl">🇺🇸</span>
                                            </div>
                                            <div>
                                                <h3 class="text-2xl font-bold text-gray-800">United States</h3>
                                                <p class="text-[#0297FE] text-sm font-semibold">Business Office</p>
                                            </div>
                                        </div>
                                        <div class="bg-pink-50 rounded-lg p-4">
                                            <p class="font-bold text-pink-900 mb-2">Blu Lavelle LLC</p>
                                            <p class="text-gray-700">
                                                5900 BALCONES DR STE 100<br>
                                                AUSTIN, TX 78731<br>
                                                United States
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Warehouses -->
                            <div class="mb-8">
                                <div class="text-center mb-10">
                                    <h2 class="text-4xl font-bold text-gray-800 mb-3">Warehouse Locations</h2>
                                    <div class="flex justify-center">
                                        <div class="w-20 h-1 bg-gradient-to-r from-orange-500 to-red-500 rounded"></div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- US Warehouse -->
                                    <div class="bg-gradient-to-br from-orange-50 to-red-50 rounded-lg p-6 shadow-xl border-2 border-orange-300">
                                        <div class="flex items-start mb-4">
                                            <div class="flex-shrink-0 w-14 h-14 bg-gradient-to-br from-orange-500 to-red-500 rounded-full flex items-center justify-center mr-4 shadow-lg">
                                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="flex items-center mb-1">
                                                    <span class="text-2xl mr-2">🇺🇸</span>
                                                    <h3 class="text-2xl font-bold text-gray-800">US Warehouse</h3>
                                                </div>
                                                <p class="text-orange-600 text-sm font-semibold">Distribution Center</p>
                                            </div>
                                        </div>
                                        <div class="bg-white rounded-lg p-4 shadow-md">
                                            <p class="text-gray-700 leading-relaxed">
                                                1301 E ARAPAHO RD, STE 101<br>
                                                RICHARDSON, TX 75081<br>
                                                United States
                                            </p>
                                        </div>
                                    </div>

                                    <!-- UK Warehouse -->
                                    <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-lg p-6 shadow-xl border-2 border-pink-300">
                                        <div class="flex items-start mb-4">
                                            <div class="flex-shrink-0 w-14 h-14 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-full flex items-center justify-center mr-4 shadow-lg">
                                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="flex items-center mb-1">
                                                    <span class="text-2xl mr-2">🇬🇧</span>
                                                    <h3 class="text-2xl font-bold text-gray-800">UK Warehouse</h3>
                                                </div>
                                                <p class="text-[#0297FE] text-sm font-semibold">Distribution Center</p>
                                            </div>
                                        </div>
                                        <div class="bg-white rounded-lg p-4 shadow-md">
                                            <p class="text-gray-700 leading-relaxed mb-3">
                                                3 Kincraig Rd<br>
                                                Blackpool FY2 0FY<br>
                                                United Kingdom
                                            </p>
                                            <div class="border-t border-pink-200 pt-3">
                                                <p class="text-gray-700">
                                                    <strong>📞 Phone:</strong> <a href="tel:02045136359" class="text-[#0297FE] hover:text-[#d6386a] font-semibold">020 4513 6359</a>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Call to Action -->
                            <div class="bg-gradient-to-r from-[#0297FE] via-[#f89192] to-[#d6386a] rounded-lg p-10 text-center text-white">
                                <div class="flex justify-center mb-6">
                                    <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center backdrop-blur-sm">
                                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path>
                                        </svg>
                                    </div>
                                </div>
                                <h3 class="text-3xl font-bold mb-4">Ready to Connect?</h3>
                                <p class="text-xl text-green-100 mb-6 max-w-3xl mx-auto">
                                    We\'re here 24/7 to answer your questions and provide support. Choose your preferred method and get in touch today!
                                </p>
                                <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                                    <a href="mailto:admin@blulavelle.com" class="inline-flex items-center px-8 py-4 bg-white text-[#0297FE] font-bold rounded-lg shadow-xl hover:shadow-2xl transition duration-200 text-lg">
                                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        Email Us
                                    </a>
                                    <a href="https://wa.me/18563782798" target="_blank" class="inline-flex items-center px-8 py-4 bg-transparent border-2 border-white text-white font-bold rounded-lg hover:bg-white hover:text-[#0297FE] transition duration-200 text-lg">
                                        <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"></path>
                                        </svg>
                                        WhatsApp
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>',
                'excerpt' => 'Contact Blu Lavelle - Email, phone, WhatsApp, iMessage. Global offices in UK, US, Vietnam, Hong Kong with warehouse locations.',
                'status' => 'published',
                'published_at' => now(),
                'template' => 'default',
                'show_in_menu' => true,
                'menu_title' => 'Contact',
                'sort_order' => 8,
                'meta_title' => 'Contact Us - Blu Lavelle Global Support',
                'meta_description' => 'Contact Blu Lavelle through email, phone, WhatsApp, or iMessage. Find our global offices in UK, US, Vietnam, Hong Kong and warehouse locations.',
            ],
            [
                'user_id' => $admin->id,
                'title' => 'Shipping & Delivery',
                'slug' => 'shipping-delivery',
                'content' => '<div class="max-w-7xl mx-auto py-8 px-4">
                    <div class="bg-white rounded-lg shadow-2xl overflow-hidden">
                        <!-- Hero Header -->
                        <div class="relative bg-gradient-to-r from-[#0297FE] via-[#f89192] to-[#d6386a] text-white px-8 py-16">
                            <div class="absolute inset-0 bg-black opacity-10"></div>
                            <div class="relative z-10 text-center">
                                <div class="flex justify-center mb-6">
                                    <div class="w-32 h-32 bg-white bg-opacity-20 rounded-full flex items-center justify-center backdrop-blur-sm shadow-2xl">
                                        <svg class="w-20 h-20 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                                        </svg>
                                    </div>
                                </div>
                                <h1 class="text-6xl font-bold mb-4">Shipping & Delivery</h1>
                                <p class="text-2xl text-pink-100">Fast, Reliable Worldwide Shipping</p>
                                <p class="text-lg text-pink-100 mt-4">Updated: Jan 3, 2025</p>
                            </div>
                        </div>

                        <div class="px-8 py-12">
                            <!-- Processing Notice -->
                            <div class="bg-gradient-to-br from-pink-50 to-pink-100 border-l-4 border-[#0297FE] rounded-r-lg p-8 mb-10">
                                <p class="text-xl text-gray-800 leading-relaxed text-center">
                                    Your product will enter the <strong class="text-[#0297FE]">processing stage</strong> as soon as your order is placed.
                                </p>
                            </div>

                            <!-- Timeline Factors -->
                            <div class="mb-12">
                                <h2 class="text-4xl font-bold text-gray-800 text-center mb-8">Delivery Timeline</h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-lg p-8 shadow-xl border-2 border-pink-300">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 w-16 h-16 bg-gradient-to-br from-[#0297FE] to-[#d6386a] rounded-full flex items-center justify-center mr-4 shadow-lg">
                                                <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="text-2xl font-bold text-pink-900 mb-3">Processing Time</h3>
                                                <p class="text-gray-800 leading-relaxed">
                                                    After your payment is confirmed, your order will enter the processing stage and usually takes <strong class="text-[#0297FE]">1 - 7 days</strong> depending on the product you purchase.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-lg p-8 shadow-xl border-2 border-pink-300">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 w-16 h-16 bg-gradient-to-br from-[#0297FE] to-[#d6386a] rounded-full flex items-center justify-center mr-4 shadow-lg">
                                                <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="text-2xl font-bold text-pink-900 mb-3">Shipping Time</h3>
                                                <p class="text-gray-800 leading-relaxed">
                                                    Once the processing is complete, your order will be shipped and will take a few more days to reach your address.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Delivery Times Table -->
                            <div class="mb-12">
                                <div class="bg-gradient-to-r from-[#0297FE] to-[#d6386a] text-white px-6 py-5 rounded-t-lg">
                                    <h2 class="text-3xl font-bold text-center">Delivery Times by Product</h2>
                                </div>
                                <div class="bg-white border-2 border-pink-300 border-t-0 rounded-b-lg overflow-x-auto">
                                    <table class="w-full">
                                        <thead class="bg-indigo-100">
                                            <tr>
                                                <th class="px-4 py-4 text-left font-bold text-gray-800 border-b-2 border-pink-300">Product</th>
                                                <th class="px-4 py-4 text-center font-bold text-gray-800 border-b-2 border-pink-300" colspan="2">Standard Delivery</th>
                                                <th class="px-4 py-4 text-center font-bold text-gray-800 border-b-2 border-pink-300" colspan="2">Premium Delivery</th>
                                                <th class="px-4 py-4 text-center font-bold text-gray-800 border-b-2 border-pink-300" colspan="2">Express Delivery</th>
                                            </tr>
                                            <tr class="bg-pink-50">
                                                <th class="px-4 py-2 text-left text-sm text-gray-600"></th>
                                                <th class="px-4 py-2 text-center text-sm text-gray-600 border-l border-pink-200">Handling</th>
                                                <th class="px-4 py-2 text-center text-sm text-gray-600">Transit</th>
                                                <th class="px-4 py-2 text-center text-sm text-gray-600 border-l border-pink-200">Handling</th>
                                                <th class="px-4 py-2 text-center text-sm text-gray-600">Transit</th>
                                                <th class="px-4 py-2 text-center text-sm text-gray-600 border-l border-pink-200">Handling</th>
                                                <th class="px-4 py-2 text-center text-sm text-gray-600">Transit</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            <tr class="hover:bg-pink-50 transition">
                                                <td class="px-4 py-4 font-semibold text-gray-800">2D Apparel</td>
                                                <td class="px-4 py-4 text-center border-l border-gray-200">1-5</td>
                                                <td class="px-4 py-4 text-center">2-7</td>
                                                <td class="px-4 py-4 text-center border-l border-gray-200">1-5</td>
                                                <td class="px-4 py-4 text-center">2-5</td>
                                                <td class="px-4 py-4 text-center border-l border-gray-200">1-4</td>
                                                <td class="px-4 py-4 text-center">2-3</td>
                                            </tr>
                                            <tr class="hover:bg-pink-50 transition bg-gray-50">
                                                <td class="px-4 py-4 font-semibold text-gray-800">Mugs</td>
                                                <td class="px-4 py-4 text-center border-l border-gray-200">1-5</td>
                                                <td class="px-4 py-4 text-center">2-9</td>
                                                <td class="px-4 py-4 text-center border-l border-gray-200">1-5</td>
                                                <td class="px-4 py-4 text-center">2-7</td>
                                                <td class="px-4 py-4 text-center border-l border-gray-200">1-4</td>
                                                <td class="px-4 py-4 text-center">2-5</td>
                                            </tr>
                                            <tr class="hover:bg-pink-50 transition">
                                                <td class="px-4 py-4 font-semibold text-gray-800">3D Hoodies</td>
                                                <td class="px-4 py-4 text-center border-l border-gray-200">1-5</td>
                                                <td class="px-4 py-4 text-center">4-12</td>
                                                <td class="px-4 py-4 text-center border-l border-gray-200">1-5</td>
                                                <td class="px-4 py-4 text-center">4-7</td>
                                                <td class="px-4 py-4 text-center border-l border-gray-200">1-4</td>
                                                <td class="px-4 py-4 text-center">2-5</td>
                                            </tr>
                                            <tr class="hover:bg-pink-50 transition bg-gray-50">
                                                <td class="px-4 py-4 font-semibold text-gray-800">Pillows</td>
                                                <td class="px-4 py-4 text-center border-l border-gray-200">1-5</td>
                                                <td class="px-4 py-4 text-center">4-12</td>
                                                <td class="px-4 py-4 text-center border-l border-gray-200">1-5</td>
                                                <td class="px-4 py-4 text-center">4-7</td>
                                                <td class="px-4 py-4 text-center border-l border-gray-200">1-4</td>
                                                <td class="px-4 py-4 text-center">2-5</td>
                                            </tr>
                                            <tr class="hover:bg-pink-50 transition">
                                                <td class="px-4 py-4 font-semibold text-gray-800">Hats</td>
                                                <td class="px-4 py-4 text-center border-l border-gray-200">1-5</td>
                                                <td class="px-4 py-4 text-center">4-12</td>
                                                <td class="px-4 py-4 text-center border-l border-gray-200">1-5</td>
                                                <td class="px-4 py-4 text-center">4-7</td>
                                                <td class="px-4 py-4 text-center border-l border-gray-200">1-4</td>
                                                <td class="px-4 py-4 text-center">2-5</td>
                                            </tr>
                                            <tr class="hover:bg-pink-50 transition bg-gray-50">
                                                <td class="px-4 py-4 font-semibold text-gray-800">Fleece Blankets</td>
                                                <td class="px-4 py-4 text-center border-l border-gray-200">1-5</td>
                                                <td class="px-4 py-4 text-center">4-12</td>
                                                <td class="px-4 py-4 text-center text-gray-400 border-l border-gray-200" colspan="2">Not Available</td>
                                                <td class="px-4 py-4 text-center text-gray-400 border-l border-gray-200" colspan="2">Not Available</td>
                                            </tr>
                                            <tr class="hover:bg-pink-50 transition">
                                                <td class="px-4 py-4 font-semibold text-gray-800">Wooden</td>
                                                <td class="px-4 py-4 text-center border-l border-gray-200">1-5</td>
                                                <td class="px-4 py-4 text-center">4-12</td>
                                                <td class="px-4 py-4 text-center border-l border-gray-200">1-5</td>
                                                <td class="px-4 py-4 text-center">4-7</td>
                                                <td class="px-4 py-4 text-center border-l border-gray-200">1-4</td>
                                                <td class="px-4 py-4 text-center">2-5</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <div class="p-4 bg-pink-50">
                                        <p class="text-pink-900 text-sm">
                                            <strong>📧 Note:</strong> Once your order has been processed, you will receive an email notification with your tracking details.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Important Notes -->
                            <div class="mb-12">
                                <div class="bg-gradient-to-r from-yellow-600 to-amber-600 text-white px-6 py-4 rounded-t-lg">
                                    <h2 class="text-3xl font-bold flex items-center">
                                        <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                        Important Shipping Notes
                                    </h2>
                                </div>
                                <div class="bg-yellow-50 border-2 border-yellow-300 border-t-0 rounded-b-lg p-6">
                                    <div class="space-y-4">
                                        <div class="bg-white rounded-lg p-5 shadow-md border-l-4 border-yellow-500">
                                            <p class="text-gray-800"><strong class="text-yellow-600">⚠️</strong> Shipping to Alaska, Hawaii, Puerto Rico can take additional <strong>7-12 business days</strong></p>
                                        </div>
                                        <div class="bg-white rounded-lg p-5 shadow-md border-l-4 border-[#0297FE]">
                                            <p class="text-gray-800"><strong class="text-[#0297FE]">ℹ️</strong> Shipping times are approximate and may vary due to customs, weather, or courier issues</p>
                                        </div>
                                        <div class="bg-white rounded-lg p-5 shadow-md border-l-4 border-purple-500">
                                            <p class="text-gray-800"><strong class="text-[#0297FE]">📦</strong> Single destination shipping only. For multiple locations, order separately</p>
                                        </div>
                                        <div class="bg-white rounded-lg p-5 shadow-md border-l-4 border-red-500">
                                            <p class="text-gray-800"><strong class="text-red-600">🏢</strong> PO boxes and Military APO/FPO available (US only). APO delivery: <strong>40-45 days</strong></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Shipping Costs -->
                            <div class="mb-12">
                                <div class="bg-gradient-to-r from-green-600 to-teal-600 text-white px-6 py-5 rounded-t-lg">
                                    <h2 class="text-3xl font-bold text-center">Shipping Costs</h2>
                                    <p class="text-center text-green-100 mt-2">Handling Fee: <strong>7%</strong> of order value</p>
                                </div>
                                
                                <!-- US Shipping Costs -->
                                {{SHIPPING_SECTION_US_START}}
                                <div class="mb-8">
                                    <div class="bg-gradient-to-r from-[#0297FE] to-[#d6386a] text-white px-6 py-3 rounded-t-lg">
                                        <h3 class="text-2xl font-bold text-center">United States Shipping</h3>
                                    </div>
                                    <div class="bg-white border-2 border-pink-300 border-t-0 rounded-b-lg overflow-x-auto">
                                        <table class="w-full">
                                            <thead class="bg-pink-100">
                                                <tr>
                                                    <th class="px-4 py-4 text-left font-bold text-gray-800 border-b-2 border-pink-300">Product Type</th>
                                                    <th class="px-4 py-4 text-center font-bold text-gray-800 border-b-2 border-pink-300">First Item</th>
                                                    <th class="px-4 py-4 text-center font-bold text-gray-800 border-b-2 border-pink-300">Additional Items</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                <tr class="hover:bg-pink-50 transition">
                                                    <td class="px-4 py-3 font-semibold text-gray-800">Press-on Nails & Nail Products</td>
                                                    <td class="px-4 py-3 text-center text-blue-700 font-bold">{{SHIPPING_US_CLOTHING_FIRST}}</td>
                                                    <td class="px-4 py-3 text-center text-gray-700">{{SHIPPING_US_CLOTHING_ADD}}</td>
                                                </tr>
                                                <tr class="hover:bg-pink-50 transition bg-gray-50">
                                                    <td class="px-4 py-3 font-semibold text-gray-800">Ornaments & Suncatchers</td>
                                                    <td class="px-4 py-3 text-center text-blue-700 font-bold">{{SHIPPING_US_ORNAMENTS_FIRST}}</td>
                                                    <td class="px-4 py-3 text-center text-gray-700">{{SHIPPING_US_ORNAMENTS_ADD}}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                {{SHIPPING_SECTION_US_END}}

                                <!-- UK Shipping Costs -->
                                {{SHIPPING_SECTION_UK_START}}
                                <div class="mb-8">
                                    <div class="bg-gradient-to-r from-[#0297FE] to-[#d6386a] text-white px-6 py-3 rounded-t-lg">
                                        <h3 class="text-2xl font-bold text-center">United Kingdom Shipping</h3>
                                    </div>
                                    <div class="bg-white border-2 border-pink-300 border-t-0 rounded-b-lg overflow-x-auto">
                                        <table class="w-full">
                                            <thead class="bg-purple-100">
                                                <tr>
                                                    <th class="px-4 py-4 text-left font-bold text-gray-800 border-b-2 border-pink-300">Product Type</th>
                                                    <th class="px-4 py-4 text-center font-bold text-gray-800 border-b-2 border-pink-300">First Item</th>
                                                    <th class="px-4 py-4 text-center font-bold text-gray-800 border-b-2 border-pink-300">Additional Items</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                <tr class="hover:bg-pink-50 transition">
                                                    <td class="px-4 py-3 font-semibold text-gray-800">Press-on Nails & Nail Products</td>
                                                    <td class="px-4 py-3 text-center text-purple-700 font-bold">{{SHIPPING_UK_CLOTHING_FIRST}}</td>
                                                    <td class="px-4 py-3 text-center text-gray-700">{{SHIPPING_UK_CLOTHING_ADD}}</td>
                                                </tr>
                                                <tr class="hover:bg-pink-50 transition bg-gray-50">
                                                    <td class="px-4 py-3 font-semibold text-gray-800">Ornaments & Suncatchers</td>
                                                    <td class="px-4 py-3 text-center text-purple-700 font-bold">{{SHIPPING_UK_ORNAMENTS_FIRST}}</td>
                                                    <td class="px-4 py-3 text-center text-gray-700">{{SHIPPING_UK_ORNAMENTS_ADD}}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                {{SHIPPING_SECTION_UK_END}}

                                <!-- Canada Shipping Costs -->
                                {{SHIPPING_SECTION_CA_START}}
                                <div class="mb-8">
                                    <div class="bg-gradient-to-r from-red-600 to-rose-600 text-white px-6 py-3 rounded-t-lg">
                                        <h3 class="text-2xl font-bold text-center">Canada Shipping</h3>
                                    </div>
                                    <div class="bg-white border-2 border-red-300 border-t-0 rounded-b-lg overflow-x-auto">
                                        <table class="w-full">
                                            <thead class="bg-red-100">
                                                <tr>
                                                    <th class="px-4 py-4 text-left font-bold text-gray-800 border-b-2 border-red-300">Product Type</th>
                                                    <th class="px-4 py-4 text-center font-bold text-gray-800 border-b-2 border-red-300">First Item</th>
                                                    <th class="px-4 py-4 text-center font-bold text-gray-800 border-b-2 border-red-300">Additional Items</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                <tr class="hover:bg-red-50 transition">
                                                    <td class="px-4 py-3 font-semibold text-gray-800">Press-on Nails & Nail Products</td>
                                                    <td class="px-4 py-3 text-center text-red-700 font-bold">{{SHIPPING_CA_CLOTHING_FIRST}}</td>
                                                    <td class="px-4 py-3 text-center text-gray-700">{{SHIPPING_CA_CLOTHING_ADD}}</td>
                                                </tr>
                                                <tr class="hover:bg-red-50 transition bg-gray-50">
                                                    <td class="px-4 py-3 font-semibold text-gray-800">Ornaments & Suncatchers</td>
                                                    <td class="px-4 py-3 text-center text-red-700 font-bold">{{SHIPPING_CA_ORNAMENTS_FIRST}}</td>
                                                    <td class="px-4 py-3 text-center text-gray-700">{{SHIPPING_CA_ORNAMENTS_ADD}}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                {{SHIPPING_SECTION_CA_END}}
                            </div>

                            <!-- Order Tracking -->
                            <div class="mb-12">
                                <div class="bg-gradient-to-br from-pink-50 to-pink-100 border-2 border-pink-300 rounded-lg p-8">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 w-16 h-16 bg-gradient-to-br from-[#0297FE] to-[#d6386a] rounded-full flex items-center justify-center mr-4 shadow-lg">
                                            <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="text-3xl font-bold text-pink-900 mb-3">Order Tracking</h3>
                                            <p class="text-gray-800 leading-relaxed text-lg">
                                                Once your order has been shipped, you will receive a <strong>tracking number via email</strong>. You can use this number to monitor your shipment\'s progress through our tracking portal or the courier\'s website.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Info Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
                                <!-- Customs -->
                                <div class="bg-gradient-to-br from-orange-50 to-red-50 border-2 border-orange-300 rounded-lg p-6">
                                    <div class="flex items-start mb-4">
                                        <div class="flex-shrink-0 w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center mr-3">
                                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-2xl font-bold text-orange-900 mb-2">Customs, Duties & Taxes</h3>
                                            <p class="text-gray-800 leading-relaxed">
                                                Orders shipped outside USA may be subject to customs duties, taxes, and fees. <strong class="text-orange-600">These charges are the customer\'s responsibility</strong> and vary by country.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Failed Deliveries -->
                                <div class="bg-gradient-to-br from-red-50 to-rose-50 border-2 border-red-300 rounded-lg p-6">
                                    <div class="flex items-start mb-4">
                                        <div class="flex-shrink-0 w-12 h-12 bg-red-500 rounded-full flex items-center justify-center mr-3">
                                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-2xl font-bold text-red-900 mb-2">Failed Deliveries</h3>
                                            <p class="text-gray-800 leading-relaxed">
                                                Blu Lavelle is <strong class="text-red-600">not responsible</strong> for packages delayed, lost, or returned due to incorrect addresses. Additional fees may apply to resend.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Section -->
                            <div class="bg-gradient-to-r from-[#0297FE] via-[#f89192] to-[#d6386a] rounded-lg p-10 text-center text-white">
                                <div class="flex justify-center mb-6">
                                    <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center backdrop-blur-sm">
                                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <h3 class="text-3xl font-bold mb-4">Questions About Shipping?</h3>
                                <p class="text-xl text-pink-100 mb-6 max-w-3xl mx-auto">
                                    If your order hasn\'t arrived or you have concerns about your shipment, we\'re here to help!
                                </p>
                                <a href="mailto:support@blulavelle.com" class="inline-flex items-center px-10 py-4 bg-white text-[#0297FE] font-bold rounded-lg shadow-xl hover:shadow-2xl transition duration-200 text-xl">
                                    <svg class="w-7 h-7 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    Contact Customer Service
                                </a>
                                <p class="mt-6 text-pink-100 text-lg">
                                    At Blu Lavelle, customer satisfaction is our priority. Thank you for choosing us!
                                </p>
                            </div>
                        </div>
                    </div>
                </div>',
                'excerpt' => 'Complete shipping and delivery information - Processing times, delivery times by product, shipping costs, tracking, customs, and more.',
                'status' => 'published',
                'published_at' => now(),
                'template' => 'default',
                'show_in_menu' => true,
                'menu_title' => 'Shipping & Delivery',
                'sort_order' => 13,
                'meta_title' => 'Shipping & Delivery - Blu Lavelle Worldwide Shipping Information',
                'meta_description' => 'Learn about Blu Lavelle shipping and delivery. Processing times, delivery times by product, shipping costs within USA, order tracking, and customs information.',
            ],
            [
                'user_id' => $admin->id,
                'title' => 'Order Tracking',
                'slug' => 'order-tracking',
                'content' => '<div class="max-w-4xl mx-auto py-10 px-4">
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-[#0297FE] to-[#d6386a] text-white px-6 py-8 text-center">
                            <h1 class="text-4xl font-bold mb-2">Order Tracking</h1>
                            <p class="text-pink-100">Check your order status with your order ID and email</p>
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="bg-pink-50 border-l-4 border-[#0297FE] rounded-lg p-5">
                                <p class="text-gray-800 mb-2">Enter the <strong>Order ID</strong> and <strong>Email</strong> you used at checkout to track:</p>
                                <ul class="list-disc list-inside text-gray-700 space-y-1">
                                    <li>Open your order confirmation email to copy the order ID</li>
                                    <li>Fill in the Order ID and Email in the tracking form</li>
                                    <li>Click <strong>Track</strong> to see the latest status</li>
                                </ul>
                            </div>
                            <div class="bg-white border border-gray-200 rounded-lg p-5">
                                <h2 class="text-xl font-semibold text-gray-900 mb-3">Can’t find your order?</h2>
                                <p class="text-gray-700 mb-3">Contact support with your order ID and email so we can check quickly.</p>
                                <a href="mailto:support@blulavelle.com" class="inline-flex items-center px-5 py-3 bg-[#0297FE] text-white rounded-lg font-semibold hover:bg-indigo-700">
                                    support@blulavelle.com
                                </a>
                            </div>
                        </div>
                    </div>
                </div>',
                'excerpt' => 'Track your Blu Lavelle order status with Order ID and email.',
                'status' => 'published',
                'published_at' => now(),
                'template' => 'default',
                'show_in_menu' => true,
                'menu_title' => 'Order Tracking',
                'sort_order' => 14,
                'meta_title' => 'Order Tracking - Blu Lavelle',
                'meta_description' => 'Track Blu Lavelle orders using Order ID and email; get support if you cannot find your order.',
            ],
            [
                'user_id' => $admin->id,
                'title' => 'Secure Payments',
                'slug' => 'secure-payments',
                'content' => '<div class="max-w-4xl mx-auto py-10 px-4">
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-green-600 to-teal-600 text-white px-6 py-8 text-center">
                            <h1 class="text-4xl font-bold mb-2">Secure Payments</h1>
                            <p class="text-green-100">Secure checkout with SSL encryption and trusted partners</p>
                        </div>
                        <div class="p-6 space-y-5">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-pink-50 border border-pink-200 rounded-lg p-4">
                                    <h3 class="text-lg font-semibold text-pink-800 mb-2">Encryption & Security</h3>
                                    <p class="text-gray-700">Card data is SSL-encrypted and processed through PCI-DSS compliant gateways.</p>
                                </div>
                                <div class="bg-pink-50 border border-pink-200 rounded-lg p-4">
                                    <h3 class="text-lg font-semibold text-[#d6386a] mb-2">Supported methods</h3>
                                    <p class="text-gray-700">Visa, MasterCard, American Express, PayPal, and popular digital wallets.</p>
                                </div>
                                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                                    <h3 class="text-lg font-semibold text-amber-800 mb-2">Fraud protection</h3>
                                    <p class="text-gray-700">Automated risk checks detect unusual transactions to protect users.</p>
                                </div>
                                <div class="bg-pink-50 border border-pink-200 rounded-lg p-4">
                                    <h3 class="text-lg font-semibold text-pink-800 mb-2">Fast support</h3>
                                    <p class="text-gray-700">Payment issue? Contact us for assistance and order confirmation.</p>
                                </div>
                            </div>
                            <div class="text-center">
                                <a href="mailto:support@blulavelle.com" class="inline-flex items-center px-6 py-3 bg-[#0297FE] text-white font-semibold rounded-lg hover:bg-[#d6386a]">support@blulavelle.com</a>
                            </div>
                        </div>
                    </div>
                </div>',
                'excerpt' => 'Information about Blu Lavelle secure payments: SSL, PCI compliance, and supported methods.',
                'status' => 'published',
                'published_at' => now(),
                'template' => 'default',
                'show_in_menu' => true,
                'menu_title' => 'Secure Payments',
                'sort_order' => 15,
                'meta_title' => 'Secure Payments - Blu Lavelle',
                'meta_description' => 'Learn how Blu Lavelle secures payments with SSL, PCI compliance, fraud protection, and supported methods.',
            ],
            [
                'user_id' => $admin->id,
                'title' => 'Help Center',
                'slug' => 'help-center',
                'content' => '<div class="max-w-5xl mx-auto py-10 px-4">
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-6 py-8 text-center">
                            <h1 class="text-4xl font-bold mb-2">Help Center</h1>
                            <p class="text-pink-100">All guides and support in one place</p>
                        </div>
                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <a href="/faqs" class="block bg-pink-50 border border-pink-200 rounded-lg p-5 hover:bg-pink-100">
                                <h3 class="text-lg font-semibold text-pink-900 mb-1">FAQs</h3>
                                <p class="text-gray-700 text-sm">Common questions about ordering, shipping, and returns.</p>
                            </a>
                            <a href="/shipping-delivery" class="block bg-pink-50 border border-pink-200 rounded-lg p-5 hover:bg-pink-100">
                                <h3 class="text-lg font-semibold text-pink-900 mb-1">Shipping & Delivery</h3>
                                <p class="text-gray-700 text-sm">Processing times, delivery windows, and shipping fees.</p>
                            </a>
                            <a href="/refund-policy" class="block bg-amber-50 border border-amber-200 rounded-lg p-5 hover:bg-amber-100">
                                <h3 class="text-lg font-semibold text-amber-900 mb-1">Refund & Returns</h3>
                                <p class="text-gray-700 text-sm">Refund policy, returns, and free-return details.</p>
                            </a>
                            <a href="/contact-us" class="block bg-pink-50 border border-pink-200 rounded-lg p-5 hover:bg-purple-100">
                                <h3 class="text-lg font-semibold text-pink-900 mb-1">Contact support</h3>
                                <p class="text-gray-700 text-sm">Submit a ticket or email for direct assistance.</p>
                            </a>
                        </div>
                        <div class="p-6 text-center">
                            <p class="text-gray-800 mb-3">Can’t find the answer? Contact us now.</p>
                            <a href="mailto:support@blulavelle.com" class="inline-flex items-center px-6 py-3 bg-[#0297FE] text-white font-semibold rounded-lg hover:bg-indigo-700">support@blulavelle.com</a>
                        </div>
                    </div>
                </div>',
                'excerpt' => 'Central help center linking FAQs, shipping, refunds, and contact support.',
                'status' => 'published',
                'published_at' => now(),
                'template' => 'default',
                'show_in_menu' => true,
                'menu_title' => 'Help Center',
                'sort_order' => 16,
                'meta_title' => 'Help Center - Blu Lavelle Support',
                'meta_description' => 'Visit Blu Lavelle Help Center for FAQs, shipping info, refund policy, and contact options.',
            ],
            [
                'user_id' => $admin->id,
                'title' => 'Sitemap',
                'slug' => 'sitemap',
                'content' => '<div class="max-w-4xl mx-auto py-10 px-4">
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-gray-700 to-slate-900 text-white px-6 py-8 text-center">
                            <h1 class="text-4xl font-bold mb-2">Sitemap</h1>
                            <p class="text-slate-200">All key pages on Blu Lavelle</p>
                        </div>
                        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <a class="block px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100" href="/about-us">About Us</a>
                            <a class="block px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100" href="/contact-us">Contact Us</a>
                            <a class="block px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100" href="/help-center">Help Center</a>
                            <a class="block px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100" href="/order-tracking">Order Tracking</a>
                            <a class="block px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100" href="/shipping-delivery">Shipping & Delivery</a>
                            <a class="block px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100" href="/refund-policy">Refund Policy</a>
                            <a class="block px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100" href="/returns-exchanges-policy">Returns & Exchanges</a>
                            <a class="block px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100" href="/free-return">Free Return</a>
                            <a class="block px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100" href="/faqs">FAQs</a>
                            <a class="block px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100" href="/privacy-policy">Privacy Policy</a>
                            <a class="block px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100" href="/terms-of-service">Terms of Service</a>
                            <a class="block px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100" href="/dmca">DMCA & IP Policy</a>
                            <a class="block px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100" href="/our-intellectual-property-policy">Our Intellectual Property Policy</a>
                            <a class="block px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100" href="/cancelchange-order">Cancel or Change Order</a>
                            <a class="block px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100" href="/secure-payments">Secure Payments</a>
                        </div>
                        <div class="p-6 text-center text-gray-700">
                            <p>Need more help? <a href="mailto:support@blulavelle.com" class="text-[#0297FE] font-semibold">support@blulavelle.com</a></p>
                        </div>
                    </div>
                </div>',
                'excerpt' => 'Sitemap listing key pages: About, Help Center, Tracking, Shipping, Policies.',
                'status' => 'published',
                'published_at' => now(),
                'template' => 'default',
                'show_in_menu' => true,
                'menu_title' => 'Sitemap',
                'sort_order' => 17,
                'meta_title' => 'Sitemap - Blu Lavelle',
                'meta_description' => 'Sitemap of Blu Lavelle including help, tracking, policies, and contact pages.',
            ],
            [
                'user_id' => $admin->id,
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => '<div class="max-w-6xl mx-auto py-8 px-4">
                    <div class="bg-white rounded-lg shadow-2xl overflow-hidden">
                        <!-- Hero Header -->
                        <div class="bg-gradient-to-r from-[#0297FE] via-[#f89192] to-[#d6386a] text-white px-8 py-12">
                            <h1 class="text-5xl font-bold mb-4">Privacy Policy</h1>
                            <p class="text-pink-100 text-xl mb-2">How Blu Lavelle collects, uses, and protects your information</p>
                            <p class="text-pink-200 text-sm">Last updated: ' . now()->format('F d, Y') . '</p>
                        </div>

                        <div class="px-8 py-8 space-y-8">
                            <div class="bg-gradient-to-br from-pink-50 to-pink-100 border-l-4 border-[#0297FE] rounded-r-lg p-6">
                                <p class="text-gray-800 leading-relaxed text-lg">
                                    This Privacy Policy explains how <strong>Blu Lavelle</strong> (the “Site”, “we”, “us”) collects, uses, and shares your personal information when you visit
                                    or make a purchase from the Site, create an account, contact support, or use any related services.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-white border border-pink-200 rounded-xl p-6">
                                    <h2 class="text-2xl font-bold text-gray-900 mb-3">1. Information we collect</h2>
                                    <ul class="list-disc list-inside text-gray-700 space-y-2">
                                        <li><strong>Account info:</strong> name, email, phone number, password (stored in hashed form).</li>
                                        <li><strong>Order info:</strong> shipping address, billing address, items purchased, and order history.</li>
                                        <li><strong>Payment info:</strong> handled by payment providers; we don’t store full card numbers.</li>
                                        <li><strong>Device data:</strong> IP address, browser type, and basic analytics about how you use the Site.</li>
                                        <li><strong>Support messages:</strong> emails and messages you send to our support team.</li>
                                    </ul>
                                </div>

                                <div class="bg-white border border-pink-200 rounded-xl p-6">
                                    <h2 class="text-2xl font-bold text-gray-900 mb-3">2. How we use your information</h2>
                                    <ul class="list-disc list-inside text-gray-700 space-y-2">
                                        <li>To process orders, deliver products, and provide customer support.</li>
                                        <li>To communicate with you about your order, account, or service updates.</li>
                                        <li>To prevent fraud, abuse, and protect the security of our users and the Site.</li>
                                        <li>To improve the Site experience, products, and services.</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="bg-white border border-pink-200 rounded-xl p-6">
                                <h2 class="text-2xl font-bold text-gray-900 mb-3">3. Cookies & analytics</h2>
                                <p class="text-gray-700 leading-relaxed mb-3">
                                    We use cookies and similar technologies to keep the Site working, remember preferences, and understand usage patterns (e.g., analytics).
                                </p>
                                <p class="text-gray-700 leading-relaxed">
                                    You can control cookies through your browser settings. Blocking some cookies may affect Site functionality.
                                </p>
                            </div>

                            <div class="bg-white border border-pink-200 rounded-xl p-6">
                                <h2 class="text-2xl font-bold text-gray-900 mb-3">4. Sharing your information</h2>
                                <p class="text-gray-700 leading-relaxed mb-3">
                                    We may share information with trusted service providers (e.g., payment processors, shipping carriers, email/analytics providers) to operate the Site and fulfill orders.
                                </p>
                                <p class="text-gray-700 leading-relaxed">
                                    We may also disclose information if required by law, to respond to legal requests, or to protect our rights and users.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-white border border-pink-200 rounded-xl p-6">
                                    <h2 class="text-2xl font-bold text-gray-900 mb-3">5. Data retention</h2>
                                    <p class="text-gray-700 leading-relaxed">
                                        We retain personal information as long as necessary to provide services, comply with legal obligations, resolve disputes, and enforce our agreements.
                                    </p>
                                </div>

                                <div class="bg-white border border-pink-200 rounded-xl p-6">
                                    <h2 class="text-2xl font-bold text-gray-900 mb-3">6. Your rights</h2>
                                    <p class="text-gray-700 leading-relaxed mb-3">
                                        Depending on your location, you may have rights to access, correct, delete, or restrict certain processing of your personal information.
                                    </p>
                                    <p class="text-gray-700 leading-relaxed">
                                        To request changes, contact us at <a class="text-[#0297FE] font-semibold underline hover:text-[#d6386a]" href="mailto:support@blulavelle.com">support@blulavelle.com</a>.
                                    </p>
                                </div>
                            </div>

                            <div class="bg-gradient-to-r from-[#0297FE] to-[#d6386a] rounded-lg p-8 text-center text-white">
                                <h3 class="text-2xl font-bold mb-2">Contact</h3>
                                <p class="text-pink-100 mb-4">Questions about privacy? We’re here to help.</p>
                                <div class="flex flex-col sm:flex-row justify-center items-center gap-3">
                                    <a href="mailto:support@blulavelle.com" class="inline-flex items-center px-8 py-3 bg-white text-[#0297FE] font-bold rounded-lg shadow-lg hover:shadow-xl transition duration-200">
                                        support@blulavelle.com
                                    </a>
                                    <a href="/contact-us" class="inline-flex items-center px-8 py-3 bg-transparent border-2 border-white text-white font-bold rounded-lg hover:bg-white hover:text-[#0297FE] transition duration-200">
                                        Contact Us
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>',
                'excerpt' => 'Privacy policy explaining how Blu Lavelle collects, uses, and protects customer information.',
                'status' => 'published',
                'published_at' => now(),
                'template' => 'default',
                'show_in_menu' => true,
                'menu_title' => 'Privacy Policy',
                'sort_order' => 18,
                'meta_title' => 'Privacy Policy - Blu Lavelle',
                'meta_description' => 'Read Blu Lavelle Privacy Policy: what data we collect, how we use it, cookies, sharing, retention, and your rights.',
            ],
        ];

        // Create pages
        foreach ($pages as $page) {
            // Normalize brand color: replace all pink accents to primary blue (#0195FE)
            if (!empty($page['content']) && is_string($page['content'])) {
                $page['content'] = str_replace(
                    [
                        // Hex codes (legacy + current)
                        '#F0427C',
                        '#f0427c',
                        '#d6386a',
                        '#D6386A',
                        '#f89192',
                        '#F89192',
                        '#0297FE',
                        '#0195FE',
                        // Layout widths (make pages wider)
                        'max-w-4xl',
                        'max-w-5xl',
                        'max-w-6xl',
                        'max-w-7xl',
                        'text-pink-',
                        'bg-pink-',
                        'border-pink-',
                        'from-pink-',
                        'to-pink-',
                        'via-pink-',
                    ],
                    [
                        '#0195FE',
                        '#0195FE',
                        '#0195FE',
                        '#0195FE',
                        '#0195FE',
                        '#0195FE',
                        '#0195FE',
                        '#0195FE',
                        'max-w-screen-2xl',
                        'max-w-screen-2xl',
                        'max-w-screen-2xl',
                        'max-w-screen-2xl',
                        'text-sky-',
                        'bg-sky-',
                        'border-sky-',
                        'from-sky-',
                        'to-sky-',
                        'via-sky-',
                    ],
                    $page['content']
                );
            }

            Page::create($page);
            $this->command->info("✓ Created page: {$page['title']}");
        }

        $this->command->info('Done! Created ' . count($pages) . ' pages.');
    }
}
