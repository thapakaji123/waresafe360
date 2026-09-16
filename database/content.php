<?php

declare(strict_types=1);

/**
 * Prototype academic content derived from the PRD. It must be validated before
 * use as formal workplace safety instruction.
 */

$hazard = static function (
    string $code,
    string $title,
    string $category,
    string $severity,
    float $yaw,
    float $pitch,
    string $risk,
    string $action,
    bool $assessed = true
): array {
    return [
        'code' => $code,
        'title' => $title,
        'category' => $category,
        'severity' => $severity,
        'yaw' => $yaw,
        'pitch' => $pitch,
        'hint' => 'Scan the ' . strtolower($category) . ' area carefully.',
        'is_assessed' => $assessed,
        'question' => 'What is the main safety concern created by this condition?',
        'options' => [
            $risk,
            'It only affects stock appearance',
            'It causes a network connection problem',
            'It creates an administrative delay only',
        ],
        'correct' => 0,
        'explanation' => $risk . '. Recommended response: ' . $action . '.',
    ];
};

return [
    [
        'code' => 'TUTORIAL',
        'title' => 'Orientation & Tutorial',
        'description' => 'Learn to pan, select a hotspot, answer a question, and understand the training display.',
        'sequence' => 0,
        'panorama' => '/assets/panoramas/tutorial.png',
        'tutorial' => true,
        'time_limit' => null,
        'hazards' => [
            $hazard('T-01', 'Practice safety marker', 'Tutorial', 'low', 0.25, -0.05, 'This is a practice interaction used to learn the controls', 'Select an answer, read the feedback, then continue', false),
        ],
    ],
    [
        'code' => 'LOADING_BAY',
        'title' => 'Loading Bay Hazard Hunt',
        'description' => 'Identify operational hazards around an active warehouse receiving and loading area.',
        'sequence' => 1,
        'panorama' => '/assets/panoramas/loading-bay.png',
        'tutorial' => false,
        'time_limit' => 420,
        'hazards' => [
            $hazard('LB-01', 'Wet floor near an active route', 'Slip / trip', 'high', -2.45, -0.38, 'Reduced traction can cause a slip and fall', 'Control the area and clean or report the spill according to site procedure'),
            $hazard('LB-02', 'Pallet obstructing a marked walkway', 'Housekeeping / access', 'high', -1.62, -0.25, 'Pedestrians may trip or move into an unsafe vehicle route', 'Keep the marked pedestrian route clear'),
            $hazard('LB-03', 'Worker close to the vehicle movement area', 'Vehicle / pedestrian', 'critical', -0.72, -0.08, 'A moving vehicle could strike the pedestrian', 'Maintain the designated separation and crossing controls'),
            $hazard('LB-04', 'Unstable stack of cartons', 'Storage / falling object', 'high', 0.08, 0.12, 'Items may fall or the stack may collapse', 'Isolate and restack the load using the approved method'),
            $hazard('LB-05', 'Damaged electrical lead across the route', 'Electrical / trip', 'high', 0.91, -0.32, 'The lead creates electrical and trip hazards', 'Stop use, protect the route, and report the damaged equipment'),
            $hazard('LB-06', 'Emergency exit partially blocked', 'Emergency access', 'critical', 1.68, -0.08, 'Obstruction can delay safe evacuation', 'Remove the obstruction and keep the exit route clear'),
            $hazard('LB-07', 'Worker missing required visible PPE', 'PPE / unsafe act', 'medium', 2.38, 0.03, 'The worker may be exposed without the required protective control', 'Follow the site risk assessment and PPE procedure'),
            $hazard('LB-08', 'Loose item near the loading edge', 'Falling object', 'high', 3.02, -0.14, 'The item may fall from the loading edge and injure someone', 'Secure or move the item away from the edge'),
        ],
    ],
    [
        'code' => 'STORAGE_AISLES',
        'title' => 'Storage Aisles',
        'description' => 'Recognise storage, housekeeping, access, and working-at-height hazards.',
        'sequence' => 2,
        'panorama' => '/assets/panoramas/storage-aisles.png',
        'tutorial' => false,
        'time_limit' => null,
        'hazards' => [
            $hazard('SA-01', 'Material protruding into the aisle', 'Collision / access', 'high', -2.75, 0.02, 'People or equipment may collide with the protruding material', 'Reposition and secure the material within the storage boundary'),
            $hazard('SA-02', 'Heavy item stored insecurely at height', 'Falling object / storage', 'critical', -1.95, 0.42, 'A heavy object may fall onto people below', 'Isolate the area and secure the item using the approved storage method'),
            $hazard('SA-03', 'Object blocking safety equipment', 'Emergency access', 'high', -1.12, -0.02, 'Emergency equipment may not be reached quickly', 'Keep the designated access area unobstructed'),
            $hazard('SA-04', 'Damaged packaging on the rack', 'Storage integrity', 'medium', -0.24, 0.18, 'Contents may shift, leak, or fall because the packaging is compromised', 'Inspect and repackage or isolate it according to procedure'),
            $hazard('SA-05', 'Debris in a pedestrian route', 'Slip / trip', 'medium', 0.64, -0.37, 'Debris can cause a trip or obstruct safe movement', 'Remove or report the debris promptly'),
            $hazard('SA-06', 'Unsafe access to upper storage', 'Working at height', 'critical', 1.43, 0.25, 'A person may fall while using unsuitable access equipment', 'Use approved access equipment and the site working-at-height procedure'),
            $hazard('SA-07', 'Poor vehicle and pedestrian separation', 'Traffic management', 'critical', 2.21, -0.05, 'Vehicles and pedestrians may enter the same space unexpectedly', 'Restore clear route separation and crossing controls'),
            $hazard('SA-08', 'Unsecured loose materials', 'Falling object', 'high', 2.91, 0.31, 'Loose materials may move or fall from storage', 'Secure the materials before the area remains in use'),
        ],
    ],
    [
        'code' => 'FORKLIFT_ZONE',
        'title' => 'Forklift & Pedestrian Zone',
        'description' => 'Focus on vehicle movement, visibility, route separation, and unsafe behaviour.',
        'sequence' => 3,
        'panorama' => '/assets/panoramas/forklift-zone.png',
        'tutorial' => false,
        'time_limit' => null,
        'hazards' => [
            $hazard('FP-01', 'Pedestrian inside an active forklift route', 'Vehicle / pedestrian', 'critical', -2.58, -0.08, 'A forklift may collide with the pedestrian', 'Stop and restore safe route separation'),
            $hazard('FP-02', 'Driver sight line blocked by the load', 'Visibility', 'critical', -1.73, 0.05, 'The operator may not see people or obstacles', 'Use the approved travel method and maintain a clear view'),
            $hazard('FP-03', 'Forklift parked in an unsuitable position', 'Vehicle safety', 'high', -0.83, -0.19, 'The vehicle may obstruct routes or create an uncontrolled hazard', 'Park and secure it only in the designated area'),
            $hazard('FP-04', 'Ignored pedestrian crossing point', 'Traffic management', 'high', 0.10, -0.03, 'Unpredictable crossing increases collision risk', 'Use the designated crossing and follow traffic controls'),
            $hazard('FP-05', 'Distracted pedestrian using a phone', 'Unsafe act', 'high', 1.05, -0.08, 'Distraction reduces awareness of moving vehicles and warnings', 'Stop in a safe place before using the device'),
            $hazard('FP-06', 'Unstable load reducing visibility', 'Load safety', 'critical', 1.92, 0.10, 'The load may shift and the operator cannot see the route clearly', 'Secure and reposition the load before travel'),
            $hazard('FP-07', 'Vehicle route narrowed by stored material', 'Housekeeping / access', 'high', 2.76, -0.22, 'Reduced clearance increases collision and obstruction risk', 'Move the materials back into the approved storage area'),
        ],
    ],
    [
        'code' => 'PACKING_DISPATCH',
        'title' => 'Packing & Dispatch',
        'description' => 'Identify manual-handling, equipment, visibility, and housekeeping hazards.',
        'sequence' => 4,
        'panorama' => '/assets/panoramas/packing-dispatch.png',
        'tutorial' => false,
        'time_limit' => null,
        'hazards' => [
            $hazard('PD-01', 'Box encouraging poor lifting posture', 'Manual handling', 'high', -2.71, -0.28, 'An awkward lift may cause musculoskeletal injury', 'Assess the load and use an appropriate handling method or aid'),
            $hazard('PD-02', 'Overloaded unstable trolley', 'Manual handling / equipment', 'high', -1.81, -0.13, 'The trolley may become difficult to control or shed its load', 'Reduce and secure the load within equipment limits'),
            $hazard('PD-03', 'Loose packaging material on the floor', 'Slip / trip', 'medium', -0.91, -0.39, 'Packaging can cause a slip or trip', 'Remove it and maintain good housekeeping'),
            $hazard('PD-04', 'Sharp damaged packaging edge', 'Handling hazard', 'medium', 0.02, -0.07, 'The damaged edge may cut a worker during handling', 'Isolate or make the package safe using the approved method'),
            $hazard('PD-05', 'Dispatch items blocking the walkway', 'Access', 'high', 0.96, -0.25, 'The obstruction can cause trips and force unsafe movement', 'Clear the marked walkway'),
            $hazard('PD-06', 'Cable creating a trip point', 'Electrical / trip', 'high', 1.92, -0.36, 'The cable can trip a worker and may become damaged', 'Reroute or protect the cable and inspect it for damage'),
            $hazard('PD-07', 'Worker carrying a load that blocks vision', 'Manual handling / visibility', 'high', 2.82, 0.00, 'The worker cannot see obstacles, people, or route changes', 'Reduce the load or use suitable handling equipment'),
        ],
    ],
    [
        'code' => 'EMERGENCY_FIRE',
        'title' => 'Emergency & Fire Safety',
        'description' => 'Recognise conditions that obstruct evacuation or emergency response.',
        'sequence' => 5,
        'panorama' => '/assets/panoramas/emergency-fire.png',
        'tutorial' => false,
        'time_limit' => null,
        'hazards' => [
            $hazard('EF-01', 'Blocked fire exit', 'Emergency access', 'critical', -2.55, -0.08, 'People may be delayed or unable to evacuate safely', 'Clear the exit and keep the full route available'),
            $hazard('EF-02', 'Fire extinguisher access obstructed', 'Fire safety', 'critical', -1.55, 0.00, 'Emergency equipment may not be reached quickly', 'Remove the obstruction and preserve the marked clearance'),
            $hazard('EF-03', 'Combustible waste accumulated nearby', 'Fire risk', 'critical', -0.52, -0.25, 'The waste can add fuel to a fire and obstruct housekeeping', 'Remove it using the site waste-control procedure'),
            $hazard('EF-04', 'Emergency signage obscured', 'Emergency communication', 'high', 0.55, 0.26, 'People may not identify the correct evacuation route quickly', 'Restore clear visibility of the sign'),
            $hazard('EF-05', 'Evacuation route narrowed by storage', 'Evacuation route', 'critical', 1.63, -0.18, 'The narrowed route may delay evacuation', 'Remove stored items from the protected route'),
            $hazard('EF-06', 'Damaged electrical equipment left in service', 'Electrical / fire risk', 'critical', 2.69, -0.07, 'The damaged equipment may cause electric shock or fire', 'Stop use, isolate it safely, and report it according to procedure'),
        ],
    ],
];
