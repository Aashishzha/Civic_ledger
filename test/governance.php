<?php
/**
 * CivicLedger - Governance Rules & Nepal Startup Policies
 * Complete platform governance with all 11 rules
 */
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$userTrustScore = getTrustScore($user['id']);
global $conn;

getHeader('Platform Governance');
?>

<div class="max-w-6xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="text-center mb-12">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-indigo-600 to-purple-700 rounded-2xl mb-6">
            <i class="fas fa-shield-alt text-3xl text-white"></i>
        </div>
        <h1 class="text-4xl font-bold text-gray-800 mb-4">CivicLedger Governance</h1>
        <p class="text-xl text-gray-600 max-w-3xl mx-auto">Building trust and accountability in Nepal's startup ecosystem through transparent governance rules</p>
        
        <!-- User Trust Score -->
        <div class="mt-8 inline-flex items-center gap-4 bg-white px-6 py-3 rounded-full shadow-lg">
            <span class="text-gray-600">Your Trust Score:</span>
            <span class="px-4 py-2 rounded-full font-bold text-xl <?php echo getTrustBadgeClass($userTrustScore); ?> border-2">
                <?php echo $userTrustScore; ?>
            </span>
            <span class="text-sm <?php echo getTrustBadgeClass($userTrustScore); ?> px-2 py-1 rounded-full">
                <?php echo getTrustLabel($userTrustScore); ?>
            </span>
        </div>
    </div>

    <!-- Rules Grid -->
    <div class="space-y-8">
        
        <!-- Rule #1: Trust Score System -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-purple-600 to-indigo-600 p-6 text-white">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-star text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold">Rule #1: Elite Trust Score System</h2>
                        <p class="text-purple-200">Gamified Reputation for All Users</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="grid md:grid-cols-2 gap-8">
                    <div>
                        <h3 class="font-bold text-lg mb-4 text-gray-800"><i class="fas fa-plus-circle text-green-500 mr-2"></i>Positive Loops (+Points)</h3>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-3">
                                <span class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                    <i class="fas fa-check text-green-600"></i>
                                </span>
                                <div>
                                    <p class="font-semibold text-gray-800">Milestone Completed On Time</p>
                                    <p class="text-sm text-gray-500">+5 points per milestone</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                    <i class="fas fa-check text-green-600"></i>
                                </span>
                                <div>
                                    <p class="font-semibold text-gray-800">Progress Update Submitted</p>
                                    <p class="text-sm text-gray-500">+2 points per update</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                    <i class="fas fa-check text-green-600"></i>
                                </span>
                                <div>
                                    <p class="font-semibold text-gray-800">Problem Upvoted by Community</p>
                                    <p class="text-sm text-gray-500">+1 point per upvote</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                    <i class="fas fa-check text-green-600"></i>
                                </span>
                                <div>
                                    <p class="font-semibold text-gray-800">Escrow Released On Time</p>
                                    <p class="text-sm text-gray-500">+5 points</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                    <i class="fas fa-check text-green-600"></i>
                                </span>
                                <div>
                                    <p class="font-semibold text-gray-800">Gold Badge Earned</p>
                                    <p class="text-sm text-gray-500">+15 points (after 3+ projects)</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg mb-4 text-gray-800"><i class="fas fa-minus-circle text-red-500 mr-2"></i>Negative Loops (-Points)</h3>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-3">
                                <span class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                    <i class="fas fa-times text-red-600"></i>
                                </span>
                                <div>
                                    <p class="font-semibold text-gray-800">Submit Late</p>
                                    <p class="text-sm text-gray-500">-10 points</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                    <i class="fas fa-times text-red-600"></i>
                                </span>
                                <div>
                                    <p class="font-semibold text-gray-800">Ghost Project (14+ days inactive)</p>
                                    <p class="text-sm text-gray-500">-15 points</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                    <i class="fas fa-times text-red-600"></i>
                                </span>
                                <div>
                                    <p class="font-semibold text-gray-800">Fake Problem Posted</p>
                                    <p class="text-sm text-gray-500">-25 points + ban</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                    <i class="fas fa-times text-red-600"></i>
                                </span>
                                <div>
                                    <p class="font-semibold text-gray-800">Compliance Violation</p>
                                    <p class="text-sm text-gray-500">-15 to -50 points</p>
                                </div>
                            </li>
                        </ul>
                        
                        <div class="mt-6 p-4 bg-amber-50 border border-amber-200 rounded-xl">
                            <p class="font-bold text-amber-800"><i class="fas fa-exclamation-triangle mr-2"></i>Warning</p>
                            <p class="text-sm text-amber-700 mt-1">If your score falls below <strong>60</strong>, you lose access to high-paying micro-gigs (above Rs. 25,000).</p>
                        </div>
                    </div>
                </div>
                
                <!-- Trust Score Tiers -->
                <div class="mt-8 grid grid-cols-5 gap-4">
                    <div class="text-center p-4 rounded-xl bg-red-50 border-2 border-red-200">
                        <div class="text-3xl font-bold text-red-600">0-59</div>
                        <div class="text-sm text-red-800 mt-1">At Risk</div>
                        <div class="text-xs text-red-600 mt-2">Limited access</div>
                    </div>
                    <div class="text-center p-4 rounded-xl bg-amber-50 border-2 border-amber-200">
                        <div class="text-3xl font-bold text-amber-600">60-79</div>
                        <div class="text-sm text-amber-800 mt-1">Active</div>
                        <div class="text-xs text-amber-600 mt-2">Standard access</div>
                    </div>
                    <div class="text-center p-4 rounded-xl bg-blue-50 border-2 border-blue-200">
                        <div class="text-3xl font-bold text-blue-600">80-99</div>
                        <div class="text-sm text-blue-800 mt-1">Verified</div>
                        <div class="text-xs text-blue-600 mt-2">Full access</div>
                    </div>
                    <div class="text-center p-4 rounded-xl bg-green-50 border-2 border-green-200">
                        <div class="text-3xl font-bold text-green-600">100-119</div>
                        <div class="text-sm text-green-800 mt-1">Trusted</div>
                        <div class="text-xs text-green-600 mt-2">Featured priority</div>
                    </div>
                    <div class="text-center p-4 rounded-xl bg-purple-50 border-2 border-purple-200">
                        <div class="text-3xl font-bold text-purple-600">120+</div>
                        <div class="text-sm text-purple-800 mt-1">Elite</div>
                        <div class="text-xs text-purple-600 mt-2">Top tier access</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rule #2: Proof of Progress -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-cyan-600 p-6 text-white">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-chart-line text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold">Rule #2: Proof of Progress (PoP)</h2>
                        <p class="text-blue-200">14-Day Progress Update Requirement</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="grid md:grid-cols-2 gap-8">
                    <div>
                        <h3 class="font-bold text-lg mb-4 text-gray-800">How It Works</h3>
                        <div class="space-y-4">
                            <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-xl">
                                <div class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">1</div>
                                <div>
                                    <p class="font-semibold">Teams must log at least one "Progress Update" every 14 days</p>
                                    <p class="text-sm text-gray-500 mt-1">Accepted: Code commit link, prototype photo, mentor sign-off, documents</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-xl">
                                <div class="w-10 h-10 bg-amber-600 text-white rounded-full flex items-center justify-center font-bold">!</div>
                                <div>
                                    <p class="font-semibold">If team goes dark for 14+ days without extension request</p>
                                    <p class="text-sm text-gray-500 mt-1">Project marked as "Inactive" and opened for other teams to claim</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-xl">
                                <div class="w-10 h-10 bg-red-600 text-white rounded-full flex items-center justify-center font-bold">!</div>
                                <div>
                                    <p class="font-semibold">Sponsor is automatically alerted</p>
                                    <p class="text-sm text-gray-500 mt-1">All team members receive trust score penalty</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg mb-4 text-gray-800">Progress Update Types</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-4 border border-gray-200 rounded-xl text-center">
                                <i class="fas fa-code text-2xl text-gray-600 mb-2"></i>
                                <p class="font-semibold text-sm">Code Commit Link</p>
                            </div>
                            <div class="p-4 border border-gray-200 rounded-xl text-center">
                                <i class="fas fa-camera text-2xl text-gray-600 mb-2"></i>
                                <p class="font-semibold text-sm">Prototype Photo</p>
                            </div>
                            <div class="p-4 border border-gray-200 rounded-xl text-center">
                                <i class="fas fa-signature text-2xl text-gray-600 mb-2"></i>
                                <p class="font-semibold text-sm">Mentor Sign-off</p>
                            </div>
                            <div class="p-4 border border-gray-200 rounded-xl text-center">
                                <i class="fas fa-file-alt text-2xl text-gray-600 mb-2"></i>
                                <p class="font-semibold text-sm">Document Upload</p>
                            </div>
                        </div>
                        
                        <div class="mt-6 p-4 bg-green-50 border border-green-200 rounded-xl">
                            <p class="font-bold text-green-800"><i class="fas fa-lightbulb mr-2"></i>Pro Tip</p>
                            <p class="text-sm text-green-700 mt-1">Regular progress updates increase your trust score and make sponsors more likely to fund your projects!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rule #3 & #4: First-Look & Dispute Resolution -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-amber-500 to-orange-600 p-6 text-white">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-gavel text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold">Rule #3 & #4: First-Look Rights & Dispute Resolution</h2>
                        <p class="text-amber-200">Sponsor Loyalty & Fair Arbitration</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="grid md:grid-cols-2 gap-8">
                    <div class="bg-gradient-to-br from-amber-50 to-orange-50 p-6 rounded-xl border border-amber-200">
                        <h3 class="font-bold text-lg mb-4 text-amber-800"><i class="fas fa-eye mr-2"></i>First-Look Rights (48 Hours)</h3>
                        <p class="text-gray-700 mb-4">Sponsors who have successfully funded at least one milestone get exclusive early access.</p>
                        <ul class="space-y-2 text-sm">
                            <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> 48-hour early access to high-upvoted problems</li>
                            <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> First view of top-tier student teams</li>
                            <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Exclusive talent scouting for HR/CSR</li>
                            <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Priority listing on sponsor marketplace</li>
                        </ul>
                        <div class="mt-4 p-3 bg-white rounded-lg">
                            <p class="text-sm font-semibold">Your funded milestones: <span class="text-amber-600"><?php echo getSponsorFundedMilestonesCount($user['id']); ?></span></p>
                            <?php if (getSponsorFundedMilestonesCount($user['id']) >= 1): ?>
                            <span class="inline-block mt-2 px-3 py-1 bg-amber-100 text-amber-800 rounded-full text-xs font-bold">
                                <i class="fas fa-crown mr-1"></i>First-Look Eligible
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-red-50 to-pink-50 p-6 rounded-xl border border-red-200">
                        <h3 class="font-bold text-lg mb-4 text-red-800"><i class="fas fa-balance-scale mr-2"></i>Dispute Resolution Protocol</h3>
                        <p class="text-gray-700 mb-4">Fair arbitration by verified platform mentors.</p>
                        <div class="space-y-3">
                            <div class="flex items-start gap-3">
                                <span class="w-6 h-6 bg-red-600 text-white rounded-full flex items-center justify-center text-xs flex-shrink-0">1</span>
                                <p class="text-sm">Dispute raised → Funds locked in escrow</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="w-6 h-6 bg-red-600 text-white rounded-full flex items-center justify-center text-xs flex-shrink-0">2</span>
                                <p class="text-sm">Independent mentor assigned (PCPS faculty/expert)</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="w-6 h-6 bg-red-600 text-white rounded-full flex items-center justify-center text-xs flex-shrink-0">3</span>
                                <p class="text-sm">Mentor reviews milestones vs. agreement</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="w-6 h-6 bg-red-600 text-white rounded-full flex items-center justify-center text-xs flex-shrink-0">4</span>
                                <p class="text-sm">Final decision: Release to team OR refund sponsor</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rules #5-7: Nepal Startup Policies -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-emerald-600 to-teal-600 p-6 text-white">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-flag text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold">Rules #5-7: Nepal Startup Policies</h2>
                        <p class="text-emerald-200">Verification, IP Protection & Local Impact</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="grid md:grid-cols-3 gap-6">
                    <!-- Rule 5 -->
                    <div class="border border-gray-200 rounded-xl p-5">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="w-8 h-8 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center font-bold">5</span>
                            <h3 class="font-bold text-gray-800">Startup Verification</h3>
                        </div>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Company registration certificate</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>PAN/VAT number</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Founder identity verification</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Escrow agreement acceptance</li>
                        </ul>
                        <div class="mt-4 p-3 bg-emerald-50 rounded-lg">
                            <p class="text-xs text-emerald-800"><strong>Benefit:</strong> Verified badge + access to premium challenges</p>
                        </div>
                        <?php if ($user['role'] === 'sponsor'): ?>
                        <p class="mt-3 text-xs <?php echo isSponsorVerified($user['id']) ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo isSponsorVerified($user['id']) ? '<i class="fas fa-check-circle"></i> Verified Sponsor' : '<i class="fas fa-times-circle"></i> Not Verified'; ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Rule 6 -->
                    <div class="border border-gray-200 rounded-xl p-5">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="w-8 h-8 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center font-bold">6</span>
                            <h3 class="font-bold text-gray-800">IP Protection</h3>
                        </div>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li><i class="fas fa-lock text-purple-500 mr-2"></i>Timestamped submission records</li>
                            <li><i class="fas fa-users text-purple-500 mr-2"></i>Team ownership verification</li>
                            <li><i class="fas fa-file-signature text-purple-500 mr-2"></i>Digital proof of submission</li>
                            <li><i class="fas fa-hand-holding-usd text-purple-500 mr-2"></i>Exclusive IP can be purchased</li>
                        </ul>
                        <div class="mt-4 p-3 bg-purple-50 rounded-lg">
                            <p class="text-xs text-purple-800"><strong>Result:</strong> Students feel safe sharing innovative ideas</p>
                        </div>
                    </div>
                    
                    <!-- Rule 7 -->
                    <div class="border border-gray-200 rounded-xl p-5">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-bold">7</span>
                            <h3 class="font-bold text-gray-800">Local Impact (SDG)</h3>
                        </div>
                        <p class="text-sm text-gray-600 mb-3">Projects must contribute to at least one:</p>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <span class="px-2 py-1 bg-gray-100 rounded"><i class="fas fa-briefcase mr-1"></i>Employment</span>
                            <span class="px-2 py-1 bg-gray-100 rounded"><i class="fas fa-graduation-cap mr-1"></i>Education</span>
                            <span class="px-2 py-1 bg-gray-100 rounded"><i class="fas fa-seedling mr-1"></i>Agriculture</span>
                            <span class="px-2 py-1 bg-gray-100 rounded"><i class="fas fa-mountain mr-1"></i>Tourism</span>
                            <span class="px-2 py-1 bg-gray-100 rounded"><i class="fas fa-leaf mr-1"></i>Environment</span>
                            <span class="px-2 py-1 bg-gray-100 rounded"><i class="fas fa-laptop mr-1"></i>Digital</span>
                        </div>
                        <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                            <p class="text-xs text-blue-800"><strong>SDG Impact Score:</strong> Higher impact = More visibility</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rules #8-11: Commitment, Mentor, Talent, Compliance -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-700 p-6 text-white">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-scroll text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold">Rules #8-11: Additional Policies</h2>
                        <p class="text-indigo-200">Escrow, Mentor Validation, Talent Pipeline & Compliance</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="grid md:grid-cols-2 gap-8">
                    <div class="space-y-6">
                        <!-- Rule 8 -->
                        <div class="p-5 bg-gradient-to-br from-amber-50 to-yellow-50 rounded-xl border border-amber-200">
                            <h3 class="font-bold text-lg text-amber-800 mb-3"><span class="w-8 h-8 bg-amber-600 text-white rounded-full flex items-center justify-center text-sm inline-block mr-2">8</span>Startup Commitment Deposit</h3>
                            <p class="text-sm text-gray-600 mb-3">Before posting a paid challenge, startups deposit 10-20% into escrow.</p>
                            <div class="bg-white p-4 rounded-lg">
                                <p class="text-sm"><strong>Example:</strong></p>
                                <p class="text-sm">Challenge Prize = Rs. 50,000</p>
                                <p class="text-sm font-bold text-amber-600">Deposit Required = Rs. 5,000-10,000</p>
                            </div>
                            <p class="text-xs text-amber-700 mt-3">If sponsor disappears after selecting winner, deposit may be transferred to affected teams.</p>
                        </div>
                        
                        <!-- Rule 9 -->
                        <div class="p-5 bg-gradient-to-br from-cyan-50 to-blue-50 rounded-xl border border-cyan-200">
                            <h3 class="font-bold text-lg text-cyan-800 mb-3"><span class="w-8 h-8 bg-cyan-600 text-white rounded-full flex items-center justify-center text-sm inline-block mr-2">9</span>Mentor Validation System</h3>
                            <p class="text-sm text-gray-600 mb-3">Challenges above Rs. 100,000 require at least one approved mentor.</p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 bg-white rounded-full text-xs border border-cyan-300">College Faculty</span>
                                <span class="px-3 py-1 bg-white rounded-full text-xs border border-cyan-300">Industry Expert</span>
                                <span class="px-3 py-1 bg-white rounded-full text-xs border border-cyan-300">Startup Founder</span>
                                <span class="px-3 py-1 bg-white rounded-full text-xs border border-cyan-300">Certified Professional</span>
                            </div>
                            <p class="text-xs text-cyan-700 mt-3">Purpose: Reduce disputes, improve quality, increase sponsor confidence</p>
                        </div>
                    </div>
                    
                    <div class="space-y-6">
                        <!-- Rule 10 -->
                        <div class="p-5 bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl border border-purple-200">
                            <h3 class="font-bold text-lg text-purple-800 mb-3"><span class="w-8 h-8 bg-purple-600 text-white rounded-full flex items-center justify-center text-sm inline-block mr-2">10</span>Talent Pipeline (Gold Badge)</h3>
                            <p class="text-sm text-gray-600 mb-3">After successfully completing 3 projects, teams receive trusted status.</p>
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 bg-gradient-to-br from-yellow-400 to-amber-500 rounded-full flex items-center justify-center">
                                    <i class="fas fa-award text-2xl text-white"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-purple-800">Elite Gold Badge</p>
                                    <ul class="text-xs text-gray-600 mt-1 space-y-1">
                                        <li><i class="fas fa-star text-yellow-500 mr-1"></i>Priority hiring visibility</li>
                                        <li><i class="fas fa-briefcase text-yellow-500 mr-1"></i>Internship recommendations</li>
                                        <li><i class="fas fa-eye text-yellow-500 mr-1"></i>Early access to top talent</li>
                                    </ul>
                                </div>
                            </div>
                            <?php if ($user['role'] === 'student'): ?>
                            <a href="team_formation.php" class="inline-block mt-4 px-4 py-2 bg-purple-600 text-white rounded-lg text-sm hover:bg-purple-700">
                                <i class="fas fa-users mr-2"></i>View Your Team
                            </a>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Rule 11 -->
                        <div class="p-5 bg-gradient-to-br from-red-50 to-rose-50 rounded-xl border border-red-200">
                            <h3 class="font-bold text-lg text-red-800 mb-3"><span class="w-8 h-8 bg-red-600 text-white rounded-full flex items-center justify-center text-sm inline-block mr-2">11</span>Nepal Compliance & Ethics</h3>
                            <p class="text-sm text-gray-600 mb-3">Projects are prohibited if they involve:</p>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <span class="px-2 py-1 bg-red-100 text-red-700 rounded"><i class="fas fa-ban mr-1"></i>Fraud</span>
                                <span class="px-2 py-1 bg-red-100 text-red-700 rounded"><i class="fas fa-ban mr-1"></i>Gambling</span>
                                <span class="px-2 py-1 bg-red-100 text-red-700 rounded"><i class="fas fa-ban mr-1"></i>Illegal Finance</span>
                                <span class="px-2 py-1 bg-red-100 text-red-700 rounded"><i class="fas fa-ban mr-1"></i>Academic Cheating</span>
                                <span class="px-2 py-1 bg-red-100 text-red-700 rounded"><i class="fas fa-ban mr-1"></i>Privacy Violation</span>
                                <span class="px-2 py-1 bg-red-100 text-red-700 rounded"><i class="fas fa-ban mr-1"></i>Hate Speech</span>
                                <span class="px-2 py-1 bg-red-100 text-red-700 rounded col-span-2"><i class="fas fa-ban mr-1"></i>Copyright Infringement</span>
                            </div>
                            <div class="mt-4 p-3 bg-white rounded-lg border border-red-200">
                                <p class="text-sm font-semibold text-red-700">Violations result in:</p>
                                <ul class="text-xs text-gray-600 mt-1 space-y-1">
                                    <li><i class="fas fa-exclamation-circle text-red-500 mr-1"></i>Immediate suspension</li>
                                    <li><i class="fas fa-exclamation-circle text-red-500 mr-1"></i>Trust score reduction</li>
                                    <li><i class="fas fa-exclamation-circle text-red-500 mr-1"></i>Permanent ban for repeated offenses</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="bg-gradient-to-r from-gray-800 to-gray-900 rounded-2xl p-8 text-white">
            <h2 class="text-2xl font-bold mb-6 text-center">Platform Statistics</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <?php
                $stats = getStats();
                $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM teams WHERE gold_badge = 1");
                $stmt->execute();
                $r = $stmt->get_result();
                $goldTeams = $r ? $r->fetch_assoc()['cnt'] : 0;
                
                $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM teams WHERE is_inactive = 1");
                $stmt->execute();
                $r = $stmt->get_result();
                $inactiveTeams = $r ? $r->fetch_assoc()['cnt'] : 0;
                ?>
                <div class="text-center">
                    <div class="text-4xl font-bold text-blue-400"><?php echo $stats['problems']; ?></div>
                    <p class="text-gray-400 mt-1">Problems Posted</p>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-green-400"><?php echo $stats['solutions']; ?></div>
                    <p class="text-gray-400 mt-1">Solutions Approved</p>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-amber-400"><?php echo $goldTeams; ?></div>
                    <p class="text-gray-400 mt-1">Gold Badge Teams</p>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-purple-400"><?php echo formatCurrency($stats['rewards']); ?></div>
                    <p class="text-gray-400 mt-1">Total Rewards</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php getFooter(); ?>