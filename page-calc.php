<?php
/**
 * Template Name: Calculator Page
 * Description: A custom template for displaying the calc page of the WordPress site.
 * This template is used to render the homepage content and layout.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Avantage_Baccarat
 */

get_header();
?>

    <main id="primary" class="site-main">
        
        <!-- Hero Section -->
        <section id="hero" class=" py-5">
            <div class="container">
                <div class="row justify-content-center text-center">
                    <div class="col-lg-10">
                        <h1 class="display-4 fw-bold mb-4">💰 Avantage Payout Calculator</h1>
                        
                        <div class=" mb-5">
                            <p class="lead text-light-gray mb-4">Understand how prize pools are divided before you enter.</p>
                            <p class="fs-5 text-light-gray mb-4">Avantage uses a pool betting model — meaning the more participants, the bigger the prizes.</p>
                            
                            <div class="row g-4 mt-4 mb-4">
                                <div class="col-md-6">
                                    <div class="p-4 rounded-3 hero-earning-method h-100">
                                        <p class="fs-5 text-yellow mb-3">Only the top 30% of players win payouts in each contest.</p>
                                        <p class="fs-5 text-light-gray">And payouts are weighted by rank — finish higher, win more.</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-4 rounded-3 hero-earning-method h-100">
                                        <p class="fs-5 text-light-gray mb-3">This calculator shows your estimated prize for each rank, based on:</p>
                                        <ul class="list-unstyled text-start fs-6 text-light-gray">
                                            <li class="mb-2">• The number of players in the contest</li>
                                            <li class="mb-2">• The entry level ($1, $10, $100, or $1,000)</li>
                                            <li class="mb-2">• The 90/10 pool split (90% to players, 10% to the house)</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4 mb-4">
                                <p class="fs-5 text-yellow mb-3">🔢 Use the calculator below to explore different scenarios.</p>
                                <p class="fs-5 text-light-gray mb-4">Pick your contest size and entry level, and see the projected payout for every prize position.</p>
                            </div>
                            
                            <div class="alert alert-warning bg-dark border-warning text-light mt-4">
                                <h6 class="text-yellow mb-3">Reminder:</h6>
                                <p class="mb-2">These projections apply to:</p>
                                <ul class="list-unstyled mb-2">
                                    <li class="mb-2">✅ One-Hand Contests (every 7 minutes)</li>
                                    <li class="mb-2">✅ The Final Round of multi-level tournaments (e.g., the Avantage World Championship)</li>
                                </ul>
                                <p class="mb-0 text-warning">Earlier rounds in tournaments are winner-advancement only — no payout until you reach the final.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Calculator Section -->
        <section class="calculator-section">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8 mx-auto">
                        <!-- Calculator start -->
                        <div class="competition-jss280 competition-jss281 competition-jss278 competition-jss279" style="    margin-bottom: 20px;">
                            <div class="competition-jss280 competition-jss293 competition-jss282">
                                <nav aria-label="breadcrumb">
                                    <a href="#" rel="noopener">Home</a> > 
                                    <a href="#" rel="noopener">Contests</a> > 
                                    <span>Calculator</span>
                                </nav>
                            </div>
                            
                            <div class="competition-jss280 competition-jss338 competition-jss284">
                                <h3 class="competition-jss302 competition-jss285 competition-jss309">Payout Calculator</h3>
                            </div>
                            
                            <div class="competition-jss280 competition-jss339 competition-jss283">
                                <div class="competition-jss280 competition-jss340">
                                    <div class="competition-jss341 competition-jss343">
                                        <span class="competition-jss342">Calculate potential payouts based on player count</span>
                                    </div>
                                </div>
                                
                                <div class="competition-jss280 competition-jss344 competition-jss288 competition-jss289">
                                    <h6 class="competition-jss302 competition-jss313">
                                        <div class="competition-jss280 competition-jss345">Calculator Summary</div>
                                    </h6>
                                    <h6 class="competition-jss302 competition-jss313">
                                        <div class="competition-jss280 competition-jss346">
                                            Enter the number of players to see how prize pools are distributed. The calculator shows estimated payouts for each ranking position based on Avantage's pool betting model. Adjust the player count to explore different scenarios and understand potential winnings.
                                        </div>
                                    </h6>
                                </div>
                                
                                <div class="competition-jss280 competition-jss347 competition-jss290 competition-jss289">
                                    <h6 class="competition-jss302 competition-jss313">
                                        <div class="competition-jss280 competition-jss348">Contest Calculator</div>
                                    </h6>
                                    
                                    <div class="competition-jss366 competition-jss354 competition-jss367 competition-jss390 competition-jss386">
                                        <!-- Current Column -->
                                        <div class="competition-jss366 competition-jss354 competition-jss368 competition-jss405">
                                            <h6 class="competition-jss302 competition-jss352 competition-jss313">Current</h6>
                                            <h6 class="competition-jss302 competition-jss352 competition-jss353 competition-jss313">11000 Players</h6>
                                            <div class="competition-jss280 competition-jss469">
                                                <div class="competition-jss280 competition-jss470 competition-jss355">
                                                    <p class="competition-jss302 competition-jss356 competition-jss303">1st</p>
                                                    <p class="competition-jss302 competition-jss356 competition-jss358 competition-jss303" id="current-prize1">
                                                    </p>
                                                </div>
                                                <div class="competition-jss280 competition-jss477 competition-jss355">
                                                    <p class="competition-jss302 competition-jss356 competition-jss303">2nd</p>
                                                    <p class="competition-jss302 competition-jss356 competition-jss358 competition-jss303" id="current-prize2">
                                                    </p>
                                                </div>
                                                <div class="competition-jss280 competition-jss481 competition-jss355">
                                                    <p class="competition-jss302 competition-jss356 competition-jss303">3rd</p>
                                                    <p class="competition-jss302 competition-jss356 competition-jss358 competition-jss303" id="current-prize3">
                                                    </p>
                                                </div>
                                                <div class="competition-jss280 competition-jss485 competition-jss355">
                                                    <p class="competition-jss302 competition-jss356 competition-jss303">4th</p>
                                                    <p class="competition-jss302 competition-jss356 competition-jss358 competition-jss303" id="current-prize4">
                                                    </p>
                                                </div>
                                                <div class="competition-jss280 competition-jss489 competition-jss355">
                                                    <p class="competition-jss302 competition-jss356 competition-jss303">5th</p>
                                                    <p class="competition-jss302 competition-jss356 competition-jss358 competition-jss303" id="current-prize5">
                                                    </p>
                                                </div>
                                                <div class="competition-jss280 competition-jss493 competition-jss355">
                                                    <p class="competition-jss302 competition-jss356 competition-jss303">6th</p>
                                                    <p class="competition-jss302 competition-jss356 competition-jss358 competition-jss303" id="current-prize6">
                                                    </p>
                                                </div>
                                                <div class="competition-jss280 competition-jss497 competition-jss355">
                                                    <p class="competition-jss302 competition-jss356 competition-jss303">7th</p>
                                                    <p class="competition-jss302 competition-jss356 competition-jss358 competition-jss303" id="current-prize7">
                                                    </p>
                                                </div>
                                                <div class="competition-jss280 competition-jss501 competition-jss355">
                                                    <p class="competition-jss302 competition-jss356 competition-jss303">8th</p>
                                                    <p class="competition-jss302 competition-jss356 competition-jss358 competition-jss303" id="current-prize8">
                                                    </p>
                                                </div>
                                                <div class="competition-jss280 competition-jss505 competition-jss355">
                                                    <p class="competition-jss302 competition-jss356 competition-jss303">9th</p>
                                                    <p class="competition-jss302 competition-jss356 competition-jss358 competition-jss303" id="current-prize9">
                                                    </p>
                                                </div>
                                                <div class="competition-jss280 competition-jss509 competition-jss355">
                                                    <p class="competition-jss302 competition-jss356 competition-jss303">10th</p>
                                                    <p class="competition-jss302 competition-jss356 competition-jss358 competition-jss303" id="current-prize10">
                                                    </p>
                                                </div>
                                                <p class="competition-jss302 competition-jss304" style="font-size: 14px; margin-top: 15px;" id="current-additional-payouts">
                                                    Additional payouts up to 3300 places!
                                                </p>
                                            </div>
                                        </div>
                                        
                                        <!-- Calculate Column -->
                                        <div class="competition-jss366 competition-jss368 competition-jss405" >
                                            <h6 class="competition-jss302 competition-jss352 competition-jss313">Calculate</h6>
                                            <div class="competition-jss280 competition-jss513 competition-jss353" style="margin-top: 0px;">
                                                <div class="competition-jss280 competition-jss514">
                                                    <span class="competition-jss360 player-circle-btn" onclick="decrementPlayers()">-</span>
                                                    <input type="number" class="competition-jss361" id="playerCount" value="13008" onchange="calculatePrizes()">
                                                    <span class="competition-jss360 player-circle-btn" onclick="incrementPlayers()">+</span>
                                                    <style>
                                                    .player-circle-btn {
                                                        font-weight: bold;
                                                        font-size: 1.3rem;
                                                        margin: 0!important;
                                                    }
                                                    .competition-jss514 {
                                                        justify-content: center;
                                                    }
                                                    </style>
                                                </div>
                                            </div>
                                            
                                            <div id="calculatedPrizes">
                                                <div class="competition-jss280 competition-jss515 competition-jss355">
                                                    <p class="competition-jss302 competition-jss356 competition-jss303">1st</p>
                                                    <p class="competition-jss302 competition-jss356 competition-jss358 competition-jss303" id="prize1">
                                                    </p>
                                                </div>
                                                <div class="competition-jss280 competition-jss519 competition-jss355">
                                                    <p class="competition-jss302 competition-jss356 competition-jss303">2nd</p>
                                                    <p class="competition-jss302 competition-jss356 competition-jss358 competition-jss303" id="prize2">
                                                    </p>
                                                </div>
                                                <div class="competition-jss280 competition-jss523 competition-jss355">
                                                    <p class="competition-jss302 competition-jss356 competition-jss303">3rd</p>
                                                    <p class="competition-jss302 competition-jss356 competition-jss358 competition-jss303" id="prize3">
                                                    </p>
                                                </div>
                                                <div class="competition-jss280 competition-jss527 competition-jss355">
                                                    <p class="competition-jss302 competition-jss356 competition-jss303">4th</p>
                                                    <p class="competition-jss302 competition-jss356 competition-jss358 competition-jss303" id="prize4">
                                                    </p>
                                                </div>
                                                <div class="competition-jss280 competition-jss531 competition-jss355">
                                                    <p class="competition-jss302 competition-jss356 competition-jss303">5th</p>
                                                    <p class="competition-jss302 competition-jss356 competition-jss358 competition-jss303" id="prize5">
                                                    </p>
                                                </div>
                                                <div class="competition-jss280 competition-jss535 competition-jss355">
                                                    <p class="competition-jss302 competition-jss356 competition-jss303">6th</p>
                                                    <p class="competition-jss302 competition-jss356 competition-jss358 competition-jss303" id="prize6">
                                                    </p>
                                                </div>
                                                <div class="competition-jss280 competition-jss539 competition-jss355">
                                                    <p class="competition-jss302 competition-jss356 competition-jss303">7th</p>
                                                    <p class="competition-jss302 competition-jss356 competition-jss358 competition-jss303" id="prize7">
                                                    </p>
                                                </div>
                                                <div class="competition-jss280 competition-jss543 competition-jss355">
                                                    <p class="competition-jss302 competition-jss356 competition-jss303">8th</p>
                                                    <p class="competition-jss302 competition-jss356 competition-jss358 competition-jss303" id="prize8">
                                                    </p>
                                                </div>
                                                <div class="competition-jss280 competition-jss547 competition-jss355">
                                                    <p class="competition-jss302 competition-jss356 competition-jss303">9th</p>
                                                    <p class="competition-jss302 competition-jss356 competition-jss358 competition-jss303" id="prize9">
                                                    </p>
                                                </div>
                                                <div class="competition-jss280 competition-jss551 competition-jss355">
                                                    <p class="competition-jss302 competition-jss356 competition-jss303">10th</p>
                                                    <p class="competition-jss302 competition-jss356 competition-jss358 competition-jss303" id="prize10">
                                                    </p>
                                                </div>
                                                <p class="competition-jss302 competition-jss304" style="font-size: 14px; margin-top: 15px;" id="additionalPayouts">
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
                  
    </main><!-- #main -->

<style>
        .casino-jss30 {
            display: flex;
            flex-direction: column;
        }
        
        .competition-jss280 {
            color: #FFFFFF;
            width: 100%;
            padding: 20px;
            background: #363847;
            margin-top: 10px;
            flex-shrink: 0;
            border-radius: 8px;
        }
        
        .competition-jss283 {
            background: #2A2B37;
            border-radius: 8px 8px 0 0;
            margin-bottom: 0;
        }
        
        .competition-jss284 {
            padding: 14px 20px 15px;
            min-height: 50px;
            border-radius: 8px;
            margin-bottom: 10px;
            background-color: #1F2027;
        }
        
        .competition-jss285 {
            color: #FFFFFF;
            font-size: 18px;
            font-weight: 700;
            line-height: 20px;
        }
        
        .competition-jss286 {
            color: #FFFFFF;
            padding: 20px 20px 25px;
            box-shadow: none;
            margin-top: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            background-color: #363847;
        }
        
        .competition-jss287 {
            font-size: 22px;
            line-height: 1.25;
            letter-spacing: -0.4px;
        }
        
        .competition-jss366 {
            display: flex;
            flex-wrap: wrap;
            box-sizing: border-box;
        }
        
        .competition-jss367 {
            width: 100%;
            display: flex;
            flex-wrap: wrap;
            box-sizing: border-box;
        }
        
        .competition-jss368 {
            margin: 0;
            box-sizing: border-box;
            min-width: 0;
        }
        
        .competition-jss405 {
            display: block;
            flex-grow: 0;
            max-width: 50%;
            flex-basis: 50%;
        }
        
        .competition-jss352 {
            height: 35px;
            text-align: center;
            font-weight: 700;
        }
        
        .competition-jss405 h6.competition-jss352 {
            text-align: center;
        }
        
        .competition-jss353 {
            color: #C0A161;
            height: 35px;
        }
        
        .competition-jss355 {
            display: flex;
            padding: 0 10px;
            margin-top: 10px;
            justify-content: space-between;
        }
        
        .competition-jss356 {
            font-size: 13px;
        }
        
        .competition-jss358 {
            width: 60px;
            display: flex;
            font-weight: 600;
            justify-content: flex-start;
        }
        
        .competition-jss359 {
            text-align: right;
            font-weight: 600;
            margin-right: 10px;
        }
        
        .competition-jss360 {
            cursor: pointer;
            margin-top: 10px;
        }
        
        .competition-jss361 {
            color: rgba(192, 161, 97, 1);
            border: 1px solid #EFF2F3;
            padding: 9px 15px;
            max-width: 80px;
            text-align: center;
            font-weight: bold;
            margin-left: 12px;
            margin-right: 12px;
            border-radius: 4px;
            background-color: transparent;
        }
        
        .competition-jss361[type=number] {
            appearance: textfield;
            -moz-appearance: textfield;
        }
        
        .competition-jss361::-webkit-outer-spin-button {
            margin: 0;
            appearance: none;
            -webkit-appearance: none;
        }
        
        .competition-jss361::-webkit-inner-spin-button {
            margin: 0;
            appearance: none;
            -webkit-appearance: none;
        }
        
        .competition-jss475 {
            color: #C0A161;
            font-weight: 600;
        }
        
        .competition-jss513 {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .competition-jss514 {
            display: flex;
            align-items: center;
        }
        
        .competition-jss302 {
            margin: 0;
        }
        
        .competition-jss303 {
            font-size: 0.875rem;
            font-family: Inter,sans-serif;
            font-weight: 400;
            line-height: 1.43;
        }
        
        .competition-jss304 {
            font-size: 1rem;
            font-family: Inter,sans-serif;
            font-weight: 400;
            line-height: 1.5;
        }
        
        .competition-jss313 {
            font-size: 1rem;
            font-family: Inter,sans-serif;
            font-weight: 400;
            line-height: 1.75;
        }
        
        .competition-jss346 {
            margin-bottom: 10px;
            font-size: 14px;
            line-height: 20px;
        }
</style>

<script>
        // Avantage Payout Calculator - Based on official algorithm documentation
        // Implements inverse power law distribution with prize bucketing
        
        class PayoutCalculator {
            constructor() {
                // Configuration constants based on documentation
                this.BETA = 1.2; // Bucket distribution constant
                this.SINGLETON_BUCKETS = 4; // Top 4 buckets are always singleton
                this.MIN_WINNER_CUTS = [0.15, 0.10, 0.07]; // Default minimum percentages for top 3
                this.DECIMAL_PLACES = 2;
                this.ENTRY_FEE = 1.0; // $1 entry fee
                this.HOUSE_TAKE = 0.10; // 10% house take
            }
            
            // Main distribution function
            distributePrizePool(playerCount, entryFee = 1.0) {
                // Input validation
                if (playerCount < 1) throw new Error('Player count must be at least 1');
                
                // Calculate total prize pool
                const totalRevenue = playerCount * entryFee;
                const amount = totalRevenue * (1 - this.HOUSE_TAKE); // 90% to players
                const winnerCount = Math.floor(playerCount * 0.3); // Top 30% get paid
                const minAmount = Math.max(entryFee * 0.1, 0.01); // Minimum prize
                
                if (winnerCount < 1) return [];
                
                // Generate initial payouts using inverse power law
                const initialPayouts = this.generateInitialPayouts(amount, winnerCount, minAmount);
                
                // Bucketize prizes
                const bucketizedPayouts = this.bucketizePrizes(initialPayouts, amount, winnerCount);
                
                return bucketizedPayouts;
            }
            
            generateInitialPayouts(amount, winnerCount, minAmount) {
                // Estimate P1 (first place prize) using amortization approach
                let P1 = this.estimateP1(amount, winnerCount, minAmount);
                
                // Apply minimum percentage constraints
                const minPercentages = this.MIN_WINNER_CUTS;
                for (let i = 0; i < Math.min(minPercentages.length, winnerCount); i++) {
                    const minPrize = amount * minPercentages[i];
                    if (i === 0) {
                        P1 = Math.max(P1, minPrize);
                    }
                }
                
                // Calculate alpha (distribution constant)
                const alpha = this.calculateAlpha(P1, amount, winnerCount, minAmount);
                
                // Generate prizes using inverse power law
                const prizes = [];
                let totalUsed = 0;
                
                for (let i = 1; i <= winnerCount; i++) {
                    let prize = P1 * Math.pow(i, -alpha);
                    prize = Math.max(prize, minAmount);
                    
                    // Apply minimum percentage constraints
                    if (i <= minPercentages.length) {
                        const minPrize = amount * minPercentages[i - 1];
                        prize = Math.max(prize, minPrize);
                    }
                    
                    prizes.push(prize);
                    totalUsed += prize;
                }
                
                // Adjust if total exceeds available amount
                if (totalUsed > amount) {
                    const scaleFactor = amount / totalUsed;
                    for (let i = 0; i < prizes.length; i++) {
                        prizes[i] *= scaleFactor;
                        prizes[i] = Math.max(prizes[i], minAmount);
                    }
                }
                
                return prizes;
            }
            
            estimateP1(amount, winnerCount, minAmount) {
                // Simple estimation: distribute remaining after ensuring minimums
                const minTotal = minAmount * winnerCount;
                const available = amount - minTotal;
                
                // Use harmonic series approximation for power law distribution
                let harmonicSum = 0;
                for (let i = 1; i <= winnerCount; i++) {
                    harmonicSum += 1 / Math.pow(i, 0.8); // Approximate alpha
                }
                
                return minAmount + (available / harmonicSum);
            }
            
            calculateAlpha(P1, amount, winnerCount, minAmount) {
                // Solve for alpha using approximation
                // This is a simplified version - the real algorithm uses numerical methods
                let alpha = 0.8; // Start with reasonable guess
                
                for (let iteration = 0; iteration < 10; iteration++) {
                    let total = 0;
                    for (let i = 1; i <= winnerCount; i++) {
                        total += P1 * Math.pow(i, -alpha);
                    }
                    
                    if (Math.abs(total - amount) < amount * 0.01) break; // Close enough
                    
                    // Adjust alpha
                    if (total > amount) {
                        alpha += 0.1;
                    } else {
                        alpha -= 0.1;
                    }
                    alpha = Math.max(0.1, Math.min(2.0, alpha));
                }
                
                return alpha;
            }
            
            bucketizePrizes(prizes, totalAmount, winnerCount) {
                if (prizes.length === 0) return [];
                
                // Calculate number of buckets
                const numBuckets = Math.min(
                    Math.ceil(Math.log(winnerCount) * this.BETA) + this.SINGLETON_BUCKETS,
                    winnerCount
                );
                
                // Create bucket structure
                const buckets = this.createBuckets(numBuckets, winnerCount);
                const bucketizedPrizes = [];
                let prizeIndex = 0;
                let leftover = 0;
                
                for (let bucketIndex = 0; bucketIndex < buckets.length; bucketIndex++) {
                    const bucketSize = buckets[bucketIndex];
                    let bucketTotal = leftover;
                    const bucketPrizes = [];
                    
                    // Collect prizes for this bucket
                    for (let i = 0; i < bucketSize && prizeIndex < prizes.length; i++) {
                        bucketPrizes.push(prizes[prizeIndex]);
                        bucketTotal += prizes[prizeIndex];
                        prizeIndex++;
                    }
                    
                    if (bucketPrizes.length === 0) break;
                    
                    // Calculate average and make it "nice"
                    let avgPrize = bucketTotal / bucketSize;
                    
                    // Don't make top prizes "nice" if they're set by minimum percentages
                    if (bucketIndex >= this.MIN_WINNER_CUTS.length) {
                        avgPrize = this.makeNiceNumber(avgPrize);
                    }
                    
                    // Calculate leftover for next bucket
                    const bucketSpent = avgPrize * bucketSize;
                    leftover = bucketTotal - bucketSpent;
                    
                    // Add prizes for this bucket
                    for (let i = 0; i < bucketSize; i++) {
                        bucketizedPrizes.push(avgPrize);
                    }
                }
                
                // Ensure monotonicity
                for (let i = 1; i < bucketizedPrizes.length; i++) {
                    if (bucketizedPrizes[i] > bucketizedPrizes[i - 1]) {
                        bucketizedPrizes[i] = bucketizedPrizes[i - 1];
                    }
                }
                
                // Distribute any remaining leftover
                if (leftover > 0) {
                    this.distributeLeftover(bucketizedPrizes, leftover);
                }
                
                return bucketizedPrizes;
            }
            
            createBuckets(numBuckets, winnerCount) {
                const buckets = [];
                
                // First SINGLETON_BUCKETS buckets have size 1
                for (let i = 0; i < Math.min(this.SINGLETON_BUCKETS, numBuckets); i++) {
                    buckets.push(1);
                }
                
                // Remaining buckets grow exponentially
                let remainingWinners = winnerCount - this.SINGLETON_BUCKETS;
                let remainingBuckets = numBuckets - this.SINGLETON_BUCKETS;
                
                if (remainingBuckets > 0 && remainingWinners > 0) {
                    let currentSize = 1;
                    for (let i = 0; i < remainingBuckets - 1; i++) {
                        currentSize = Math.ceil(currentSize * this.BETA);
                        const actualSize = Math.min(currentSize, remainingWinners);
                        buckets.push(actualSize);
                        remainingWinners -= actualSize;
                        
                        if (remainingWinners <= 0) break;
                    }
                    
                    // Last bucket gets remaining winners
                    if (remainingWinners > 0) {
                        buckets.push(remainingWinners);
                    }
                }
                
                return buckets;
            }
            
            makeNiceNumber(amount) {
                if (amount <= 0) return 0;
                
                // Get magnitude
                const magnitude = Math.floor(Math.log10(amount));
                const normalizedAmount = amount / Math.pow(10, magnitude);
                
                // Round down to nice number (1.5 significant digits)
                let niceNormalized;
                if (normalizedAmount >= 5) {
                    niceNormalized = 5;
                } else if (normalizedAmount >= 2) {
                    niceNormalized = 2;
                } else if (normalizedAmount >= 1.5) {
                    niceNormalized = 1.5;
                } else {
                    niceNormalized = 1;
                }
                
                return niceNormalized * Math.pow(10, magnitude);
            }
            
            distributeLeftover(prizes, leftover) {
                if (leftover <= 0 || prizes.length === 0) return;
                
                // Distribute starting from top, with constraints
                let remaining = leftover;
                
                for (let i = 0; i < prizes.length && remaining > 0; i++) {
                    const currentPrize = prizes[i];
                    const maxIncrease = i === 0 ? currentPrize * 0.1 : // 10% max for 1st place
                                       (i > 0 ? (prizes[i-1] - currentPrize) * 0.25 : 0); // 25% of gap for others
                    
                    const increase = Math.min(remaining, maxIncrease);
                    prizes[i] += increase;
                    remaining -= increase;
                }
            }
        }
        
        // Global calculator instance
        const payoutCalculator = new PayoutCalculator();
        
        // Prize calculation logic using the proper algorithm
        function calculatePrizes() {
            const playerCount = parseInt(document.getElementById('playerCount').value) || 11000;
            
            // Validate minimum player count
            if (playerCount < 11000) {
                alert('Cannot be less than the current number of registered players. Please enter a number greater than 11,000.');
                document.getElementById('playerCount').value = 11000;
                return;
            }
            
            try {
                // Use the proper algorithm
                const payouts = payoutCalculator.distributePrizePool(playerCount, 1.0);
                
                // Update display for top 10 positions
                for (let i = 0; i < 10; i++) {
                    const prizeElement = document.getElementById(`prize${i + 1}`);
                    if (prizeElement && i < payouts.length) {
                        prizeElement.textContent = formatCurrency(payouts[i]);
                    } else if (prizeElement) {
                        prizeElement.textContent = '$0.00';
                    }
                }
                
                // Update additional payouts message
                const payoutPlaces = Math.floor(playerCount * 0.3);
                document.getElementById('additionalPayouts').textContent = 
                    `Additional payouts up to ${payoutPlaces.toLocaleString()} places!`;
                    
            } catch (error) {
                console.error('Error calculating prizes:', error);
                // Fallback to simple calculation
                calculatePrizesSimple();
            }
        }
        
        // Calculate current baseline (11,000 players) prizes
        function calculateCurrentPrizes() {
            try {
                const payouts = payoutCalculator.distributePrizePool(11000, 1.0);
                
                for (let i = 0; i < 10; i++) {
                    const currentPrizeElement = document.getElementById(`current-prize${i + 1}`);
                    if (currentPrizeElement && i < payouts.length) {
                        currentPrizeElement.textContent = formatCurrency(payouts[i]);
                    } else if (currentPrizeElement) {
                        currentPrizeElement.textContent = '$0.00';
                    }
                }
                
                const payoutPlaces = Math.floor(11000 * 0.3);
                const currentAdditionalElement = document.getElementById('current-additional-payouts');
                if (currentAdditionalElement) {
                    currentAdditionalElement.textContent = `Additional payouts up to ${payoutPlaces.toLocaleString()} places!`;
                }
            } catch (error) {
                console.error('Error calculating current prizes:', error);
                // Fallback to simple calculation
                calculateCurrentPrizesSimple();
            }
        }
        
        // Fallback simple calculation (original method)
        function calculatePrizesSimple() {
            const playerCount = parseInt(document.getElementById('playerCount').value) || 11000;
            const basePrize = 100000000;
            const scaleFactor = playerCount / 11000;
            const totalPrizePool = basePrize * scaleFactor;
            
            const prizeDistribution = [0.165, 0.11, 0.08, 0.054, 0.041, 0.031, 0.015, 0.015, 0.01, 0.01];
            
            for (let i = 0; i < 10; i++) {
                const prize = totalPrizePool * prizeDistribution[i];
                const prizeElement = document.getElementById(`prize${i + 1}`);
                if (prizeElement) {
                    prizeElement.textContent = formatCurrency(prize);
                }
            }
        }
        
        function calculateCurrentPrizesSimple() {
            const basePrize = 100000000;
            const prizeDistribution = [0.165, 0.11, 0.08, 0.054, 0.041, 0.031, 0.015, 0.015, 0.01, 0.01];
            
            for (let i = 0; i < 10; i++) {
                const prize = basePrize * prizeDistribution[i];
                const currentPrizeElement = document.getElementById(`current-prize${i + 1}`);
                if (currentPrizeElement) {
                    currentPrizeElement.textContent = formatCurrency(prize);
                }
            }
        }
        
        function formatCurrency(amount) {
            if (amount >= 1000000) {
                return `$${(amount / 1000000).toFixed(1)}M`;
            } else if (amount >= 1000) {
                return `$${(amount / 1000).toFixed(1)}K`;
            } else {
                return `$${amount.toFixed(2)}`;
            }
        }
        
        function incrementPlayers() {
            const input = document.getElementById('playerCount');
            const currentValue = parseInt(input.value || 0);
            input.value = currentValue + 100;
            calculatePrizes();
        }
        
        function decrementPlayers() {
            const input = document.getElementById('playerCount');
            const currentValue = parseInt(input.value || 0);
            const newValue = currentValue - 100;
            
            if (newValue < 11000) {
                alert('Cannot be less than the current number of registered players. Please enter a number greater than 11,000.');
                return;
            }
            
            input.value = newValue;
            calculatePrizes();
        }
        
        // Initialize with current values
        document.addEventListener('DOMContentLoaded', function() {
            calculateCurrentPrizes();
            calculatePrizes();
        });
</script>

    <!-- Fixed Right Navigation Menu -->
    <nav class="fixed-nav-menu">
        <div class="nav-menu-content">
            <h6 class="nav-menu-title">Quick Navigation</h6>
            <ul class="nav-menu-list">
                <li><a href="#hero" class="nav-menu-link">Calculator</a></li>
            </ul>
        </div>
    </nav>


<style>
    
    body, h1, h2, h3, h4, h5, h6, p, a, ul, ol, li, .btn-69, .card, .card-title, .lead, .form-label, .form-control, .form-select, label, input, select, textarea, .navbar-brand, .nav-link, .carousel-item, .display-5, .fw-bold, .hero-content, .hero-image, .button-container, .card-body, .card-img-top, .text-center, .align-middle, .vh-100, .mb-3, .mb-4, .mb-5, .py-5, .container, .row, .col, .col-12, .col-md-6, .col-lg-7, .col-lg-8, .d-grid, .shadow-sm, .rounded, .bg-dark, .bg-light, .ratio, .form-check, .form-check-input, .form-check-label, .form-select, .list-unstyled, .fs-5, .fw-bold, .text-center, .justify-content-center, .align-items-center, .fade-in, .fade-in-on-scroll {
        font-family: 'Source Sans Pro', Arial, Helvetica, sans-serif !important;
        color: rgb(255, 255, 252)!important;
    }
    
    /* Custom color variables */
    :root {
        --accent-red: rgb(215, 24, 42);
        --accent-yellow: rgb(255, 149, 0);
        --light-gray: rgb(224, 224, 224);
    }
    
    .page {
    margin: 0;
}
    body {
        background-color: #000000;
        background-image: 
            radial-gradient(circle at 20% 80%, rgba(215, 24, 42, 0.1) 0%, transparent 50%),
            radial-gradient(circle at 80% 20%, rgba(255, 149, 0, 0.1) 0%, transparent 50%);
    }
    .navbar-brand, .nav-link,label {
        color: rgb(255, 255, 252)!important;

    }
   
    h1 {
        color:rgb(255, 255, 252);
        font-size: 3rem;
        margin-bottom: 30px;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    }
    h2,p {
        color: rgb(255, 255, 252);
    }
    a:visited {
        color: inherit;
    }
    
    /* Enhanced button styles */
    .btn-custom-red {
        background-color: var(--accent-red);
        border-color: var(--accent-red);
        color: white;
        transition: all 0.3s ease;
    }
    
    .btn-custom-red:hover {
        background-color: rgba(215, 24, 42, 0.8);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(215, 24, 42, 0.4);
    }
    
    .btn-custom-yellow {
        background-color: var(--accent-yellow);
        border-color: var(--accent-yellow);
        color: black;
        transition: all 0.3s ease;
    }
    
    .btn-custom-yellow:hover {
        background-color: rgba(255, 149, 0, 0.8);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(255, 149, 0, 0.4);
    }
    
    /* Animated gradient backgrounds */
    .gradient-animated {
        background: linear-gradient(-45deg, var(--accent-red), var(--accent-yellow), var(--accent-red), var(--accent-yellow));
        background-size: 400% 400%;
        animation: gradientShift 15s ease infinite;
    }
    
    @keyframes gradientShift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    
    /* Card hover effects */
    .card-hover {
        transition: all 0.3s ease;
    }
    
    .card-hover:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(215, 24, 42, 0.2);
    }
    
    /* Color utility classes */
    .text-red {
        color: var(--accent-red) !important;
    }
    
    .text-yellow {
        color: var(--accent-yellow) !important;
    }
    
    .text-light-gray {
        color: var(--light-gray) !important;
    }
    
    .font-weight-bold {
        font-weight: bold !important;
    }
    
    /* Section specific styles */
    
    /* Hero Section */
    .hero-bg {
        background: 
            linear-gradient(135deg, rgba(0, 0, 0, 0.7) 0%, rgba(0, 0, 0, 0.3) 50%, rgba(0, 0, 0, 0.8) 100%),
            url('<?php echo get_template_directory_uri(); ?>/images/chipcards.jpg');
        background-position: bottom left;
        background-repeat: no-repeat;
        position: relative;
    }
    
    .hero-earning-method {
        background: rgba(33, 37, 41, 0.8);
        border: 1px solid rgba(255, 149, 0, 0.3);
        backdrop-filter: blur(5px);
        transition: all 0.3s ease;
    }
    
    .hero-earning-method:hover {
        background: rgba(33, 37, 41, 0.9);
        border-color: rgba(255, 149, 0, 0.5);
        transform: translateY(-2px);
    }
    
    /* Streamers Section */
    .streamers-bg {
        background: linear-gradient(135deg, rgba(215, 24, 42, 0.1) 0%, rgba(0, 0, 0, 0.8) 100%);
    }
    
    .streamers-card {
        border-left: 4px solid var(--accent-red) !important;
    }
    
    .section-header-yellow {
        color: var(--accent-yellow);
    }
    
    .badge-red {
        background-color: var(--accent-red) !important;
    }
    
    .badge-yellow {
        background-color: var(--accent-yellow);
    }
    
    .total-potential-box {
        background: rgba(255, 149, 0, 0.1);
        border: 2px dashed var(--accent-yellow);
    }
    
    .total-potential-title {
        color: var(--accent-yellow);
    }
    
    .total-potential-number {
        color: var(--accent-red);
    }
    
    .total-potential-subtitle {
        color: var(--light-gray);
    }
    
    .streamers-alert {
        background: rgba(224, 224, 224, 0.1);
        border: 1px solid var(--light-gray);
        border-radius: 10px;
    }
    
    /* Players Section */
    .players-bg {
        background: linear-gradient(45deg, rgba(255, 149, 0, 0.05) 0%, rgba(0, 0, 0, 0.9) 100%);
    }
    
    .players-card {
        background: linear-gradient(135deg, rgba(224, 224, 224, 0.1) 0%, rgba(33, 37, 41, 0.95) 100%);
        border-top: 4px solid var(--accent-yellow) !important;
    }
    
    .icon-bg-red {
        background-color: rgba(215, 24, 42, 0.2);
    }
    
    .icon-bg-yellow {
        background-color: rgba(255, 149, 0, 0.2);
    }
    
    .icon-bg-gray {
        background-color: rgba(224, 224, 224, 0.2);
    }
    
    .icon-red {
        color: var(--accent-red);
    }
    
    .icon-yellow {
        color: var(--accent-yellow);
    }
    
    .icon-gray {
        color: var(--light-gray);
    }
    
    .players-cta-box {
        background: linear-gradient(135deg, rgba(215, 24, 42, 0.1) 0%, rgba(255, 149, 0, 0.1) 100%);
        border: 1px solid rgba(224, 224, 224, 0.3);
    }
    
    .btn-red {
        background-color: var(--accent-red);
        color: white;
        border: none;
    }
    
    /* Warrants Section */
    .warrants-bg {
        background: linear-gradient(135deg, rgba(224, 224, 224, 0.05) 0%, rgba(215, 24, 42, 0.1) 50%, rgba(0, 0, 0, 0.9) 100%);
        top: 0;
        left: 0;
        z-index: 1;
    }
    
    .warrants-content {
        z-index: 2;
    }
    
    .warrants-card {
        background: rgba(33, 37, 41, 0.95);
        backdrop-filter: blur(10px);
    }
    
    .warrants-header {
        background: linear-gradient(90deg, var(--accent-red) 0%, var(--accent-yellow) 100%);
        border: none;
    }
    
    .warrant-card-red {
        background: rgba(215, 24, 42, 0.1);
    }
    
    .warrant-card-yellow {
        background: rgba(255, 149, 0, 0.1);
    }
    
    .warrant-card-gray {
        background: rgba(224, 224, 224, 0.1);
    }
    
    .warrant-card-gradient {
        background: linear-gradient(135deg, rgba(215, 24, 42, 0.1) 0%, rgba(255, 149, 0, 0.1) 100%);
    }
    
    .warrant-icon-red {
        background-color: var(--accent-red);
        color: white;
    }
    
    .warrant-icon-yellow {
        background-color: var(--accent-yellow);
        color: white;
    }
    
    .warrant-icon-gray {
        background-color: var(--light-gray);
        color: black;
    }
    
    .warrant-icon-gradient {
        background: linear-gradient(135deg, var(--accent-red) 0%, var(--accent-yellow) 100%);
        color: white;
    }
    
    .warrant-bottom-line {
        background: linear-gradient(135deg, rgba(255, 149, 0, 0.1) 0%, rgba(215, 24, 42, 0.1) 100%);
        border: 2px solid rgba(224, 224, 224, 0.3);
    }
    
    .warrant-explanation {
        background: rgba(33, 37, 41, 0.5);
        border: 1px solid rgba(224, 224, 224, 0.2);
    }
    
    .example-box {
        background: rgba(255, 149, 0, 0.1);
        border: 1px solid rgba(255, 149, 0, 0.3);
    }
    
    /* Celebrity Section */
    .celebrity-bg {
        background: radial-gradient(circle at 30% 50%, rgba(255, 149, 0, 0.1) 0%, rgba(215, 24, 42, 0.05) 50%, rgba(0, 0, 0, 0.9) 100%);
        top: 0;
        left: 0;
        z-index: 1;
    }
    
    .celebrity-content {
        z-index: 2;
    }
    
    .celebrity-card {
        background: linear-gradient(135deg, rgba(33, 37, 41, 0.95) 0%, rgba(224, 224, 224, 0.05) 100%);
    }
    
    .celebrity-header {
        background: linear-gradient(45deg, var(--accent-red) 0%, var(--accent-yellow) 50%, var(--accent-red) 100%);
        border: none;
    }
    
    .celebrity-stars {
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="stars" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="white" opacity="0.3"/></pattern></defs><rect width="100" height="100" fill="url(%23stars)"/></svg>');
        opacity: 0.3;
    }
    
    .celebrity-alert {
        background: rgba(255, 149, 0, 0.1);
        border: 2px solid rgba(255, 149, 0, 0.3);
    }
    
    .celebrity-icon {
        background-color: var(--accent-yellow);
        color: black;
    }
    
    .celebrity-select-box {
        background: linear-gradient(135deg, rgba(215, 24, 42, 0.2) 0%, rgba(255, 149, 0, 0.2) 100%);
        border: 2px dashed var(--light-gray);
    }
    
    .celebrity-submit-btn {
        background: linear-gradient(135deg, var(--accent-red) 0%, var(--accent-yellow) 100%);
        color: white;
        border: none;
    }
    
    .celebrity-warning {
        background: rgba(215, 24, 42, 0.1);
        border: 2px solid rgba(215, 24, 42, 0.3);
    }
    
    .celebrity-warning-icon {
        background-color: var(--accent-red);
        color: white;
    }
    
    .celebrity-movement {
        background: linear-gradient(135deg, rgba(224, 224, 224, 0.1) 0%, rgba(255, 149, 0, 0.1) 100%);
        border: 2px solid rgba(224, 224, 224, 0.3);
    }
    
    /* CTA Section */
    .cta-bg {
        background: linear-gradient(135deg, rgba(215, 24, 42, 0.1) 0%, rgba(255, 149, 0, 0.1) 100%);
    }
    
    .cta-card {
        background: linear-gradient(135deg, rgba(33, 37, 41, 0.95) 0%, rgba(224, 224, 224, 0.1) 100%);
        border-top: 5px solid var(--accent-yellow) !important;
    }
    
    .cta-divider {
        width: 100px;
        height: 4px;
        background: linear-gradient(90deg, var(--accent-red) 0%, var(--accent-yellow) 100%);
        border-radius: 2px;
    }
    
    .cta-step-red {
        background: rgba(215, 24, 42, 0.1);
        border: 2px solid rgba(215, 24, 42, 0.3);
    }
    
    .cta-step-yellow {
        background: rgba(255, 149, 0, 0.1);
        border: 2px solid rgba(255, 149, 0, 0.3);
    }
    
    .cta-step-gray {
        background: rgba(224, 224, 224, 0.1);
        border: 2px solid rgba(224, 224, 224, 0.3);
    }
    
    .cta-badge-red {
        background-color: var(--accent-red);
    }
    
    .cta-badge-yellow {
        background-color: var(--accent-yellow);
        color: black;
    }
    
    .cta-badge-gray {
        background-color: var(--light-gray);
        color: black;
    }
    
    .cta-btn-outline-red {
        border-color: var(--accent-red) !important;
        color: var(--accent-red) !important;
    }
    
    .cta-btn-yellow {
        background-color: var(--accent-yellow);
        color: black;
        border: none;
    }
    
    .cta-btn-outline-gray {
        border-color: var(--light-gray) !important;
        color: var(--light-gray) !important;
    }
    
    /* Magic Johnson Section */
    #magic-mistake {
        position: relative;
        
    }
    
    #magic-mistake::before {
        content: "🏀";
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 15rem;
        opacity: 0.13;
        z-index: 3;
        pointer-events: none;
    }
    
    #magic-mistake .container {
        position: relative;
        z-index: 2;
    }
    
    .magic-bg {
        background: linear-gradient(135deg, rgba(215, 24, 42, 0.2) 0%, rgba(255, 149, 0, 0.1) 50%, rgba(0, 0, 0, 0.9) 100%);
    }
    
    .magic-card {
        background: linear-gradient(135deg, rgba(215, 24, 42, 0.9) 0%, rgba(255, 149, 0, 0.8) 100%);
    }
    
    .magic-header {
        background: rgba(0, 0, 0, 0.3);
        border: none;
    }
    
    .magic-bg-icon {
        top: 20px;
        right: 20px;
        opacity: 0.1;
        font-size: 8rem;
        color: white;
    }
    
    .magic-timeline-1 {
        background: rgba(0, 0, 0, 0.2);
        border-left: 4px solid white;
    }
    
    .magic-timeline-2 {
        background: rgba(0, 0, 0, 0.3);
        border-left: 4px solid var(--light-gray);
    }
    
    .magic-timeline-3 {
        background: rgba(0, 0, 0, 0.4);
        border-left: 4px solid white;
    }
    
    .magic-highlight {
        font-size: 1.2em;
    }
    
    .magic-conclusion {
        background: rgba(0, 0, 0, 0.3);
    }
    
    .magic-emphasis {
        font-size: 1.3em;
        color: white;
    }
    
    .magic-stats {
        background: rgba(0, 0, 0, 0.4);
        border: 2px dashed white;
    }
    
    .magic-divider {
        border-color: rgba(224, 224, 224, 0.5);
    }
    
    .magic-final {
        border-top: 2px solid rgba(255, 255, 255, 0.3);
    }
    
    /* Promotion Section */
    .promotion-icon {
        font-size: 1.5rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background: rgba(255, 149, 0, 0.2);
        border-radius: 50%;
        flex-shrink: 0;
    }
    
    .promotion-image-container {
        max-width: 100%;
        position: relative;
        overflow: hidden;
        border-radius: 15px;
    }
    
    .promotion-image {
        max-width: 100% !important;
        width: 100%;
        height: 330px;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .promotion-image-container:hover .promotion-image {
        transform: scale(1.05);
    }
    
    .promotion-image-container::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(255, 149, 0, 0.1) 0%, rgba(215, 24, 42, 0.1) 100%);
        pointer-events: none;
    }
    
    /* Real Streamers Section */
    .streamers-icon {
        font-size: 1.5rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background: rgba(215, 24, 42, 0.2);
        border-radius: 50%;
        flex-shrink: 0;
    }
    
    .streamers-image-container {
        max-width: 100%;
        position: relative;
        overflow: hidden;
        border-radius: 15px;
    }
    
    .streamers-image {
        max-width: 100% !important;
        width: 100%;
        height: 330px;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .streamers-image-container:hover .streamers-image {
        transform: scale(1.05);
    }
    
    .streamers-image-container::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(215, 24, 42, 0.1) 0%, rgba(255, 149, 0, 0.1) 100%);
        pointer-events: none;
    }
@media (max-width: 991px) {
    .section-blob {
        right: 10px;
        top: unset;
        bottom: 30px;
        max-width: 90vw;
        font-size: 1rem;
        padding: 18px 16px;
    }
}
@media (min-width: 768px) {
     h1 {
        font-size: 2rem;
    }
}

/* Fixed Navigation Menu */
.fixed-nav-menu {
    position: fixed;
    top: 23%;
    right: 20px;
    transform: translateY(-50%);
    z-index: 1000;
    max-height: 80vh;
    overflow-y: auto;
}

.nav-menu-content {
    background: rgba(33, 37, 41, 0.95);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 15px;
    backdrop-filter: blur(10px);
    min-width: 150px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.nav-menu-title {
    color: rgb(255, 255, 252);
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 10px;
    text-align: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding-bottom: 8px;
}

.nav-menu-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.nav-menu-list li {
    margin-bottom: 5px;
}

.nav-menu-link {
    display: block;
    color: rgba(255, 255, 252, 0.8);
    text-decoration: none;
    font-size: 0.8rem;
    padding: 6px 8px;
    border-radius: 4px;
    transition: all 0.3s ease;
    border-left: 2px solid transparent;
}

.nav-menu-link:hover {
    color: rgb(255, 255, 252);
    background: rgba(255, 255, 255, 0.1);
    border-left-color: rgb(255, 149, 0);
    text-decoration: none;
    transform: translateX(2px);
}

.nav-menu-link:focus {
    color: rgb(255, 255, 252);
    text-decoration: none;
    outline: none;
    box-shadow: 0 0 0 2px rgba(255, 149, 0, 0.5);
}

/* Smooth scrolling */
html {
    scroll-behavior: smooth;
}

/* Hide navigation on small screens */
@media (max-width: 991px) {
    .fixed-nav-menu {
        display: none;
    }
}

/* Custom scrollbar for nav menu */
.nav-menu-content::-webkit-scrollbar {
    width: 4px;
}

.nav-menu-content::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 2px;
}

.nav-menu-content::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 2px;
}

.nav-menu-content::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
}

</style>
<?php
get_footer();
